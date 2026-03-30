from ultralytics import YOLO

if __name__ == '__main__':
    # Load the pretrained YOLOv8n base model
    model = YOLO("yolov8n.pt")

    # Fine-tune on your dataset
    model.train(
        data="E:/roadside_accident_project/dataset/data.yaml",
        epochs=50,
        imgsz=640,
        batch=8,
        device=0,
        workers=2,        # ← Reduced from 8 to 2 (safer on Windows)
        project="runs/train",
        name="roadside_v1",
        patience=10,
        save=True,
        plots=True
    )