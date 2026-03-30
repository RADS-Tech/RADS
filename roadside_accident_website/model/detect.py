import sys
import json
import os
import cv2
import numpy as np
from collections import defaultdict, deque

#Tunable thresholds
CONF_THRESHOLD        = 0.30   # YOLO detection confidence minimum
SAMPLE_FPS            = 5      # frames to sample per second
ACCIDENT_THRESHOLD    = 0.50   # final confidence to classify as accident

# Rule 1 - Velocity drop
VEL_HISTORY_LEN       = 6      # frames of velocity history to keep
DECEL_RATIO_THRESH    = 0.55   # current speed < 45% of avg recent speed

# Rule 2 - Bounding box overlap
IOU_THRESH            = 0.12   # IoU above this triggers overlap rule
IOU_MIN_FRAMES        = 2      # overlap must persist for N frames to fire

# Rule 3 - Area change
AREA_CHANGE_THRESH    = 0.40   # 40% sudden change in bounding box area
AREA_MIN_FRAMES       = 2      # must occur for N frames to reduce false positives

# Rule 4 - Abnormal stop after high speed
HIGH_SPEED_THRESH     = 18.0   # pixels/frame threshold to be "fast"
STOP_SPEED_THRESH     = 3.0    # pixels/frame threshold to be "stopped"
STOP_AFTER_FAST_WIN   = 10     # frames window to check fast→stop transition

# Rule 5 - Trajectory deviation
DIRECTION_HIST_LEN    = 8      # frames for direction history
DIRECTION_DEV_THRESH  = 110    # degrees of sudden direction change

# Temporal smoothing
SMOOTH_WINDOW         = 4      # sliding window for score smoothing
TEMPORAL_PERSIST      = 3      # frames an event must persist to count

# Rule weights (must sum to 1.0)
RULE_WEIGHTS = {
    "velocity_drop":    0.30,
    "bbox_overlap":     0.25,
    "area_change":      0.15,
    "abnormal_stop":    0.20,
    "direction_change": 0.10,
}

#

def compute_iou(box1, box2):
    ix1 = max(box1[0], box2[0])
    iy1 = max(box1[1], box2[1])
    ix2 = min(box1[2], box2[2])
    iy2 = min(box1[3], box2[3])
    inter = max(0, ix2 - ix1) * max(0, iy2 - iy1)
    if inter == 0:
        return 0.0
    a1 = (box1[2]-box1[0]) * (box1[3]-box1[1])
    a2 = (box2[2]-box2[0]) * (box2[3]-box2[1])
    return inter / (a1 + a2 - inter + 1e-6)


def box_center(box):
    return ((box[0] + box[2]) / 2.0, (box[1] + box[3]) / 2.0)


def angle_between(v1, v2):
    n1 = np.linalg.norm(v1)
    n2 = np.linalg.norm(v2)
    if n1 < 1e-6 or n2 < 1e-6:
        return 0.0
    cos_a = np.clip(np.dot(v1, v2) / (n1 * n2), -1.0, 1.0)
    return float(np.degrees(np.arccos(cos_a)))


class VehicleTracker:
    
    def __init__(self, maxlen=30):
        self.centers    = deque(maxlen=maxlen)  # (cx, cy) history
        self.areas      = deque(maxlen=maxlen)  # bounding box area history
        self.speeds     = deque(maxlen=maxlen)  # pixel speed history
        self.boxes      = deque(maxlen=maxlen)  # raw box history

    def update(self, box):
        cx, cy = box_center(box)
        area   = (box[2] - box[0]) * (box[3] - box[1])

        if self.centers:
            prev_cx, prev_cy = self.centers[-1]
            speed = np.sqrt((cx - prev_cx)**2 + (cy - prev_cy)**2)
        else:
            speed = 0.0

        self.centers.append((cx, cy))
        self.areas.append(area)
        self.speeds.append(speed)
        self.boxes.append(box)


class AccidentDetector:
    
    def __init__(self):
        self.vehicles          = defaultdict(VehicleTracker)
        self.overlap_counts    = defaultdict(int)   # (id1, id2) -> consecutive overlap frames
        self.area_chg_counts   = defaultdict(int)   # track_id -> consecutive area change frames
        self.frame_scores      = []                  # per-frame rule-weighted scores
        self.frame_rule_flags  = []                  # per-frame dict of which rules fired

    def process_frame(self, track_ids, boxes):
       
        rule_scores = {k: 0.0 for k in RULE_WEIGHTS}

        # Update all trackers
        for tid, box in zip(track_ids, boxes):
            self.vehicles[tid].update(box)

        active_ids  = list(track_ids)
        active_boxes = list(boxes)
        n = len(active_ids)

        # Rule 1 - Sudden Velocity Drop (Δv deceleration)
        for tid in active_ids:
            tr = self.vehicles[tid]
            if len(tr.speeds) >= VEL_HISTORY_LEN + 1:
                recent_speeds = list(tr.speeds)
                current_speed = recent_speeds[-1]
                # Average of the previous N speeds (excluding current)
                avg_prev = np.mean(recent_speeds[-(VEL_HISTORY_LEN+1):-1])
                if avg_prev > HIGH_SPEED_THRESH * 0.5:  # only meaningful if vehicle was moving
                    ratio = current_speed / (avg_prev + 1e-6)
                    if ratio < (1.0 - DECEL_RATIO_THRESH):
                        # Magnitude of deceleration → confidence
                        decel_conf = min(1.0, (1.0 - ratio) * 1.4)
                        rule_scores["velocity_drop"] = max(rule_scores["velocity_drop"], decel_conf)

        # Rule 2 - Bounding Box Overlap (IoU)
        active_pairs = set()
        for i in range(n):
            for j in range(i+1, n):
                tid1, tid2 = active_ids[i], active_ids[j]
                iou = compute_iou(active_boxes[i], active_boxes[j])
                pair_key = (min(tid1,tid2), max(tid1,tid2))
                if iou > IOU_THRESH:
                    self.overlap_counts[pair_key] += 1
                    active_pairs.add(pair_key)
                    if self.overlap_counts[pair_key] >= IOU_MIN_FRAMES:
                        # Scale confidence with IoU magnitude
                        overlap_conf = min(1.0, 0.50 + iou * 1.8)
                        rule_scores["bbox_overlap"] = max(rule_scores["bbox_overlap"], overlap_conf)
                else:
                    self.overlap_counts[pair_key] = 0  # reset streak

        # Decay stale pairs
        stale = [k for k in self.overlap_counts if k not in active_pairs]
        for k in stale:
            self.overlap_counts[k] = max(0, self.overlap_counts[k] - 1)

        # Rule 3 - Sudden Bounding Box Area Change

        for tid in active_ids:
            tr = self.vehicles[tid]
            if len(tr.areas) >= 3:
                areas = list(tr.areas)
                # Compare current area to average of last 3 frames
                avg_recent = np.mean(areas[-4:-1])
                current_area = areas[-1]
                if avg_recent > 100:  # ignore tiny detections
                    change_ratio = abs(current_area - avg_recent) / (avg_recent + 1e-6)
                    if change_ratio > AREA_CHANGE_THRESH:
                        self.area_chg_counts[tid] += 1
                        if self.area_chg_counts[tid] >= AREA_MIN_FRAMES:
                            area_conf = min(1.0, 0.45 + change_ratio * 0.6)
                            rule_scores["area_change"] = max(rule_scores["area_change"], area_conf)
                    else:
                        self.area_chg_counts[tid] = max(0, self.area_chg_counts[tid] - 1)

        # Rule 4 - Abnormal Stop After High Speed

        for tid in active_ids:
            tr = self.vehicles[tid]
            if len(tr.speeds) >= STOP_AFTER_FAST_WIN:
                speeds = list(tr.speeds)
                current_speed = speeds[-1]
                max_recent    = max(speeds[-STOP_AFTER_FAST_WIN:-1])
                if max_recent > HIGH_SPEED_THRESH and current_speed < STOP_SPEED_THRESH:
                    # Stronger signal if faster before stopping
                    stop_conf = min(1.0, 0.55 + (max_recent / (HIGH_SPEED_THRESH * 3)) * 0.45)
                    rule_scores["abnormal_stop"] = max(rule_scores["abnormal_stop"], stop_conf)

        # Rule 5 - Trajectory Deviation (Sharp Direction Change)

        for tid in active_ids:
            tr = self.vehicles[tid]
            if len(tr.centers) >= DIRECTION_HIST_LEN:
                centers = list(tr.centers)
                mid  = len(centers) // 2
                # Direction vector in first half
                v1 = np.array(centers[mid-1]) - np.array(centers[0])
                # Direction vector in second half
                v2 = np.array(centers[-1]) - np.array(centers[mid])
                angle = angle_between(v1, v2)
                # Only meaningful if vehicle was actually moving
                total_dist = np.linalg.norm(np.array(centers[-1]) - np.array(centers[0]))
                if angle > DIRECTION_DEV_THRESH and total_dist > 10:
                    dir_conf = min(1.0, 0.40 + (angle - DIRECTION_DEV_THRESH) / 70.0 * 0.6)
                    rule_scores["direction_change"] = max(rule_scores["direction_change"], dir_conf)

        # Combine rules → frame score

        frame_score = sum(rule_scores[r] * RULE_WEIGHTS[r] for r in RULE_WEIGHTS)

        # Bonus: if 2 or more rules fire simultaneously → stronger signal
        rules_fired = [r for r in rule_scores if rule_scores[r] > 0.35]
        if len(rules_fired) >= 2:
            frame_score = min(1.0, frame_score * 1.25)

        self.frame_scores.append(frame_score)
        self.frame_rule_flags.append({r: round(rule_scores[r], 3) for r in rule_scores})

        return frame_score, rule_scores

    def compute_final_confidence(self):

        if not self.frame_scores:
            return 0.0

        scores = np.array(self.frame_scores)

        # Temporal smoothing: sliding window average
        smoothed = np.convolve(scores, np.ones(SMOOTH_WINDOW)/SMOOTH_WINDOW, mode='valid')

        if len(smoothed) == 0:
            smoothed = scores

        # Only count "sustained" peaks (smoothed score > threshold)
        sustained = smoothed[smoothed > 0.25]

        if len(sustained) == 0:
            return round(float(np.max(scores)) * 0.5, 3)

        # Final = 60% peak sustained + 40% mean of high frames
        peak_score = float(np.max(sustained))
        mean_high  = float(np.mean(sustained))
        final = 0.60 * peak_score + 0.40 * mean_high

        return round(min(1.0, final), 3)


#

def run_detection(video_path):
    try:
        from ultralytics import YOLO
    except ImportError:
        return {"accident": False, "confidence": 0.0,
                "error": "ultralytics not installed"}

    model_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'best.pt')

    if not os.path.exists(model_path):
        return {"accident": False, "confidence": 0.0,
                "error": f"Model not found at {model_path}"}

    if not os.path.exists(video_path):
        return {"accident": False, "confidence": 0.0,
                "error": f"Video not found at {video_path}"}

    model    = YOLO(model_path)
    detector = AccidentDetector()

    cap = cv2.VideoCapture(video_path)
    if not cap.isOpened():
        return {"accident": False, "confidence": 0.0, "error": "Cannot open video"}

    fps         = cap.get(cv2.CAP_PROP_FPS) or 25.0
    sample_rate = max(1, int(fps / SAMPLE_FPS))
    frame_idx   = 0
    frames_processed = 0

    while True:
        ret, frame = cap.read()
        if not ret:
            break

        if frame_idx % sample_rate != 0:
            frame_idx += 1
            continue

        # Run YOLO tracking on sampled frame
        results = model.track(frame, persist=True, verbose=False, conf=CONF_THRESHOLD)

        if results and results[0].boxes is not None:
            boxes_obj = results[0].boxes

            if boxes_obj.id is not None:
                ids   = boxes_obj.id.cpu().numpy().astype(int).tolist()
                xyxys = boxes_obj.xyxy.cpu().numpy().tolist()

                if ids and xyxys:
                    detector.process_frame(ids, xyxys)
                    frames_processed += 1

        frame_idx += 1

    cap.release()

    if frames_processed == 0:
        return {"accident": False, "confidence": 0.0,
                "error": "No tracked detections found"}

    final_conf  = detector.compute_final_confidence()
    is_accident = final_conf >= ACCIDENT_THRESHOLD

    # Summarize which rules contributed most
    all_flags  = detector.frame_rule_flags
    rule_peaks = {}
    for rule in RULE_WEIGHTS:
        vals = [f[rule] for f in all_flags if f]
        rule_peaks[rule] = round(max(vals), 3) if vals else 0.0

    return {
        "accident":   is_accident,
        "confidence": final_conf,
        "rules":      rule_peaks,
        "frames":     frames_processed
    }


#

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No video path provided"}))
        sys.exit(1)

    video_path = sys.argv[1]
    result = run_detection(video_path)

    print(json.dumps(result))
