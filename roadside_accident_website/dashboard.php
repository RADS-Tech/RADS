<?php
session_start();

if (!isset($_SESSION['operator_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RADS Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Barlow:wght@300;400;600;700;900&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg:      #0d0d0d;
      --panel:   #141414;
      --panel2:  #1a1a1a;
      --border:  #252525;
      --text:    #e0e0e0;
      --muted:   #555;
      --muted2:  #888;
      --red:     #e53935;
      --red-dim: #7a1f1d;
      --green:   #43a047;
      --yellow:  #fbc02d;
      --mono:    'Share Tech Mono', monospace;
      --sans:    'Barlow', sans-serif;
    }

    html, body { height: 100%; }
    body {
      background: var(--bg);
      color: var(--text);
      font-family: var(--sans);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    /*TOP NAV*/
    .topnav {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 24px;
      height: 52px;
      background: var(--panel);
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
      z-index: 100;
    }
    .nav-left { display: flex; align-items: center; gap: 20px; }
    .brand {
      font-family: var(--mono);
      font-size: 18px;
      font-weight: 700;
      color: #fff;
      letter-spacing: 2px;
    }
    .brand span { color: var(--red); }
    .system-badge {
      display: flex; align-items: center; gap: 7px;
      background: rgba(67,160,71,0.1);
      border: 1px solid rgba(67,160,71,0.25);
      padding: 3px 10px; border-radius: 2px;
    }
    .dot-live {
      width: 7px; height: 7px; border-radius: 50%;
      background: var(--green);
      animation: blink 1.8s ease-in-out infinite;
    }
    @keyframes blink { 0%,100%{opacity:1;} 50%{opacity:0.3;} }
    .system-badge span { font-family: var(--mono); font-size: 10px; letter-spacing: 2px; color: var(--green); }

    .nav-right { display: flex; align-items: center; gap: 16px; }
    .nav-user {
      font-family: var(--mono); font-size: 12px; color: var(--muted2);
    }
    .nav-user strong { color: var(--text); }
    .nav-location {
      font-family: var(--mono); font-size: 11px; color: var(--muted);
      display: flex; align-items: center; gap: 5px;
    }
    .nav-location::before { content: '📍'; font-size: 11px; }
    .btn-logout {
      background: transparent; border: 1px solid var(--border);
      color: var(--muted); font-family: var(--mono); font-size: 11px;
      letter-spacing: 1px; padding: 5px 12px; cursor: pointer;
      transition: border-color 0.2s, color 0.2s; border-radius: 2px;
    }
    .btn-logout:hover { border-color: var(--red); color: var(--red); }

    /*STATUS BAR*/
    .statusbar {
      display: flex;
      align-items: center;
      gap: 24px;
      padding: 0 24px;
      height: 36px;
      background: #0f0f0f;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .stat-item {
      display: flex; align-items: center; gap: 7px;
      font-family: var(--mono); font-size: 11px; color: var(--muted);
    }
    .stat-item .val { color: var(--text); }
    .stat-sep { width: 1px; height: 16px; background: var(--border); }
    .time-display { margin-left: auto; font-family: var(--mono); font-size: 12px; color: var(--muted2); }

    /*MAIN LAYOUT*/
    .main {
      display: grid;
      grid-template-columns: 1fr 360px;
      gap: 0;
      flex: 1;
      overflow: hidden;
    }

    /*LEFT PANEL*/
    .left-panel {
      display: flex;
      flex-direction: column;
      padding: 20px;
      gap: 14px;
      overflow-y: auto;
      border-right: 1px solid var(--border);
    }

    .panel-header {
      display: flex; align-items: center; justify-content: space-between;
    }
    .panel-label {
      font-family: var(--mono); font-size: 11px; letter-spacing: 2px;
      color: var(--muted); text-transform: uppercase;
    }
    .panel-tag {
      font-family: var(--mono); font-size: 10px; color: var(--muted);
      border: 1px solid var(--border); padding: 2px 8px; border-radius: 2px;
    }

    /*VIDEO BOX*/
    .video-container {
      position: relative;
      background: #0a0a0a;
      border: 1px solid var(--border);
      aspect-ratio: 16/9;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      overflow: hidden;
      transition: border-color 0.3s;
    }
    .video-container:hover { border-color: #444; }
    .video-container.has-video { cursor: default; }
    .video-container.accident-active { border-color: var(--red); box-shadow: 0 0 20px rgba(229,57,53,0.2); }

    /*scan effect on video box*/
    .video-container::before {
      content: ''; position: absolute; left: 0; right: 0;
      top: -100%; height: 60%; pointer-events: none; z-index: 2;
      background: linear-gradient(transparent, rgba(229,57,53,0.04), transparent);
      animation: vscan 4s linear infinite;
    }
    @keyframes vscan { to { top: 150%; } }

    .upload-placeholder {
      display: flex; flex-direction: column; align-items: center; gap: 14px;
      z-index: 3; position: relative;
    }
    .upload-icon {
      width: 64px; height: 64px;
      border: 2px dashed #333;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      transition: border-color 0.3s, transform 0.3s;
    }
    .video-container:hover .upload-icon { border-color: #555; transform: scale(1.05); }
    .upload-icon svg { opacity: 0.4; transition: opacity 0.3s; }
    .video-container:hover .upload-icon svg { opacity: 0.7; }
    .upload-placeholder p {
      font-family: var(--mono); font-size: 13px; color: var(--muted);
      letter-spacing: 1px;
    }
    .upload-placeholder small {
      font-size: 11px; color: #333; font-family: var(--mono);
    }

    video#mainVideo {
      width: 100%; height: 100%; object-fit: contain;
      display: none; position: absolute; inset: 0; z-index: 1;
    }

    .video-overlay {
      position: absolute; bottom: 0; left: 0; right: 0;
      padding: 10px 14px;
      background: linear-gradient(transparent, rgba(0,0,0,0.8));
      display: none; z-index: 4;
      justify-content: space-between; align-items: center;
    }
    .video-filename {
      font-family: var(--mono); font-size: 11px; color: var(--muted2);
      overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 60%;
    }
    .video-status-tag {
      font-family: var(--mono); font-size: 11px; padding: 3px 10px;
      border-radius: 2px; letter-spacing: 1px;
    }
    .video-status-tag.analyzing { background: rgba(251,192,45,0.15); color: var(--yellow); border: 1px solid rgba(251,192,45,0.3); }
    .video-status-tag.safe { background: rgba(67,160,71,0.15); color: var(--green); border: 1px solid rgba(67,160,71,0.3); }
    .video-status-tag.danger { background: rgba(229,57,53,0.15); color: var(--red); border: 1px solid rgba(229,57,53,0.3); }

    /*CONFIDENCE BAR*/
    .confidence-row {
      display: none;
      background: var(--panel);
      border: 1px solid var(--border);
      padding: 14px 16px;
      gap: 16px;
      align-items: center;
    }
    .conf-label { font-family: var(--mono); font-size: 11px; color: var(--muted); letter-spacing: 1px; white-space: nowrap; }
    .conf-bar-wrap { flex: 1; height: 6px; background: #222; border-radius: 3px; overflow: hidden; }
    .conf-bar-fill {
      height: 100%; width: 0%;
      background: linear-gradient(90deg, var(--green), var(--yellow));
      transition: width 1s ease, background 0.5s ease;
      border-radius: 3px;
    }
    .conf-bar-fill.danger { background: linear-gradient(90deg, var(--yellow), var(--red)); }
    .conf-val { font-family: var(--mono); font-size: 14px; font-weight: 700; color: var(--text); min-width: 44px; text-align: right; }

    /*DETECTION LOG*/
    .detection-log {
      background: var(--panel);
      border: 1px solid var(--border);
      flex: 1; min-height: 120px;
    }
    .log-header {
      padding: 10px 14px;
      border-bottom: 1px solid var(--border);
      font-family: var(--mono); font-size: 11px; letter-spacing: 2px;
      color: var(--muted); display: flex; align-items: center; gap: 8px;
    }
    .log-header .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--green); }
    .log-body { padding: 10px 14px; display: flex; flex-direction: column; gap: 6px; }
    .log-entry {
      font-family: var(--mono); font-size: 12px; color: var(--muted);
      display: flex; gap: 10px;
    }
    .log-entry .ts { color: #333; min-width: 80px; }
    .log-entry.info .msg { color: var(--muted2); }
    .log-entry.warn .msg { color: var(--yellow); }
    .log-entry.danger .msg { color: var(--red); }
    .log-entry.ok .msg { color: var(--green); }

    /*RIGHT PANEL*/
    .right-panel {
      display: flex; flex-direction: column;
      background: var(--panel); overflow-y: auto;
    }

    .rp-section {
      padding: 16px;
      border-bottom: 1px solid var(--border);
    }
    .rp-title {
      font-family: var(--mono); font-size: 10px; letter-spacing: 2.5px;
      color: var(--muted); text-transform: uppercase; margin-bottom: 12px;
    }

    /*Detection Status Card*/
    .status-card {
      background: #0f0f0f;
      border: 1px solid var(--border);
      padding: 16px;
      border-radius: 2px;
      text-align: center;
    }
    .status-icon {
      width: 56px; height: 56px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 12px;
      font-size: 24px;
      transition: all 0.4s ease;
    }
    .status-icon.idle { background: rgba(85,85,85,0.15); border: 2px solid #333; }
    .status-icon.running { background: rgba(251,192,45,0.1); border: 2px solid var(--yellow); animation: glow-y 1.5s ease-in-out infinite; }
    .status-icon.safe { background: rgba(67,160,71,0.1); border: 2px solid var(--green); }
    .status-icon.danger { background: rgba(229,57,53,0.1); border: 2px solid var(--red); animation: glow-r 1s ease-in-out infinite; }
    @keyframes glow-y { 0%,100%{box-shadow:0 0 8px rgba(251,192,45,0.3);} 50%{box-shadow:0 0 20px rgba(251,192,45,0.6);} }
    @keyframes glow-r { 0%,100%{box-shadow:0 0 8px rgba(229,57,53,0.4);} 50%{box-shadow:0 0 24px rgba(229,57,53,0.8);} }
    .status-label {
      font-family: var(--mono); font-size: 13px; letter-spacing: 1px;
      transition: color 0.3s;
    }
    .status-label.idle { color: var(--muted); }
    .status-label.running { color: var(--yellow); }
    .status-label.safe { color: var(--green); }
    .status-label.danger { color: var(--red); }
    .status-sub { font-size: 12px; color: var(--muted); margin-top: 4px; }

    /*Stats Grid*/
    .stats-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 8px;
    }
    .stat-box {
      background: #0f0f0f; border: 1px solid var(--border);
      padding: 12px; border-radius: 2px;
    }
    .stat-box .s-label { font-family: var(--mono); font-size: 10px; color: var(--muted); letter-spacing: 1px; margin-bottom: 4px; }
    .stat-box .s-val { font-family: var(--mono); font-size: 20px; font-weight: 700; color: var(--text); }
    .stat-box .s-val.red { color: var(--red); }
    .stat-box .s-val.green { color: var(--green); }

    /*Emergency Response*/
    .emergency-section {
      padding: 16px;
      border-bottom: 1px solid var(--border);
    }

    .question-box {
      background: rgba(229,57,53,0.05);
      border: 1px solid rgba(229,57,53,0.2);
      padding: 14px;
      border-radius: 2px;
      margin-bottom: 12px;
      display: none;
    }
    .question-box p {
      font-size: 13px; color: #ccc; margin-bottom: 12px; line-height: 1.5;
    }
    .question-box .q-label {
      font-family: var(--mono); font-size: 10px; letter-spacing: 2px;
      color: var(--red); margin-bottom: 8px; display: block;
    }
    .btn-row { display: flex; gap: 8px; }
    .btn-yes, .btn-no {
      flex: 1; padding: 10px; border: none; cursor: pointer;
      font-family: var(--mono); font-size: 12px; letter-spacing: 1px;
      border-radius: 2px; transition: all 0.2s;
    }
    .btn-yes { background: var(--red); color: #fff; }
    .btn-yes:hover { background: #c62828; }
    .btn-no { background: transparent; color: var(--muted); border: 1px solid var(--border); }
    .btn-no:hover { border-color: #555; color: var(--text); }

    .idle-notice {
      background: #0f0f0f; border: 1px solid var(--border);
      padding: 16px; border-radius: 2px; text-align: center;
    }
    .idle-notice p { font-family: var(--mono); font-size: 12px; color: var(--muted); line-height: 1.6; }

    /*Info rows*/
    .info-row {
      display: flex; justify-content: space-between; align-items: center;
      padding: 8px 0; border-bottom: 1px solid var(--border);
      font-size: 13px;
    }
    .info-row:last-child { border-bottom: none; }
    .info-row .key { color: var(--muted); font-family: var(--mono); font-size: 11px; letter-spacing: 1px; }
    .info-row .val { color: var(--text); font-weight: 600; }

    /*Upload btn*/
    .btn-upload-vid {
      width: 100%; padding: 10px;
      background: transparent; border: 1px solid var(--border);
      color: var(--muted2); font-family: var(--mono); font-size: 12px;
      letter-spacing: 1px; cursor: pointer; border-radius: 2px;
      transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-upload-vid:hover { border-color: var(--red); color: var(--red); }

    input[type="file"] { display: none; }

    /*POPUP OVERLAY*/
    .popup-overlay {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.85);
      backdrop-filter: blur(4px);
      z-index: 200;
      display: none;
      align-items: center; justify-content: center;
    }
    .popup-overlay.show { display: flex; }

    .popup {
      background: var(--panel);
      border: 1px solid var(--red);
      border-top: 3px solid var(--red);
      padding: 32px;
      width: 100%; max-width: 440px;
      position: relative;
      animation: popIn 0.3s ease both;
    }
    @keyframes popIn { from { opacity:0; transform:scale(0.92) translateY(10px); } to { opacity:1; transform:scale(1); } }

    .popup-alert-badge {
      display: flex; align-items: center; gap: 10px; margin-bottom: 20px;
    }
    .alert-icon-big {
      width: 48px; height: 48px; border-radius: 50%;
      background: rgba(229,57,53,0.15); border: 2px solid var(--red);
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; flex-shrink: 0;
      animation: glow-r 1s ease-in-out infinite;
    }
    .popup-alert-badge h2 { font-size: 20px; font-weight: 700; color: #fff; }
    .popup-alert-badge p { font-size: 12px; color: var(--muted); font-family: var(--mono); }

    .popup-detail {
      background: #0f0f0f; border: 1px solid var(--border);
      padding: 14px; border-radius: 2px; margin-bottom: 20px;
    }
    .popup-detail-row { display: flex; justify-content: space-between; padding: 5px 0; font-size: 13px; }
    .popup-detail-row .k { color: var(--muted); font-family: var(--mono); font-size: 11px; }
    .popup-detail-row .v { color: var(--text); font-weight: 600; }
    .popup-detail-row .v.red { color: var(--red); }

    .popup-timer {
      text-align: center; margin-bottom: 16px;
      font-family: var(--mono); font-size: 12px; color: var(--muted);
    }
    .popup-timer span { color: var(--yellow); font-size: 18px; font-weight: 700; }

    .popup-actions { display: flex; gap: 10px; }
    .btn-alert-yes {
      flex: 1; background: var(--red); color: #fff; border: none;
      padding: 13px; font-family: var(--mono); font-size: 13px; letter-spacing: 2px;
      cursor: pointer; border-radius: 2px; transition: background 0.2s;
    }
    .btn-alert-yes:hover { background: #c62828; }
    .btn-alert-cancel {
      flex: 1; background: transparent; color: var(--muted);
      border: 1px solid var(--border); padding: 13px;
      font-family: var(--mono); font-size: 13px; letter-spacing: 1px;
      cursor: pointer; border-radius: 2px; transition: all 0.2s;
    }
    .btn-alert-cancel:hover { border-color: #555; color: var(--text); }

    /*SUCCESS POPUP*/
    .success-popup {
      background: var(--panel);
      border: 1px solid var(--green);
      border-top: 3px solid var(--green);
      padding: 32px;
      width: 100%; max-width: 400px;
      text-align: center;
      animation: popIn 0.3s ease both;
    }
    .success-icon {
      width: 64px; height: 64px; border-radius: 50%;
      background: rgba(67,160,71,0.1); border: 2px solid var(--green);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin: 0 auto 16px;
    }
    .success-popup h2 { font-size: 20px; font-weight: 700; color: var(--green); margin-bottom: 8px; }
    .success-popup p { font-size: 13px; color: var(--muted); font-family: var(--mono); line-height: 1.6; }
    .btn-dismiss {
      margin-top: 20px; width: 100%;
      background: rgba(67,160,71,0.15); color: var(--green);
      border: 1px solid rgba(67,160,71,0.3);
      padding: 11px; font-family: var(--mono); font-size: 12px;
      letter-spacing: 2px; cursor: pointer; border-radius: 2px;
      transition: background 0.2s;
    }
    .btn-dismiss:hover { background: rgba(67,160,71,0.25); }
  </style>
</head>
<body>

<!-- TOP NAV -->
<nav class="topnav">
  <div class="nav-left">
    <div class="brand">R<span>A</span>DS</div>
    <div class="system-badge">
      <div class="dot-live"></div>
      <span>MONITORING ACTIVE</span>
    </div>
  </div>
  <div class="nav-right">
    <div class="nav-user">Operator: <strong id="navUsername">Loading...</strong></div>
    <div class="nav-location" id="navLocation">Loading location...</div>
    <button class="btn-logout" onclick="window.location='php/logout.php'">Logout</button>
  </div>
</nav>

<!-- STATUS BAR -->
<div class="statusbar">
  <div class="stat-item">
    <span>SESSION</span>
    <span class="val" id="sessionTime">00:00:00</span>
  </div>
  <div class="stat-sep"></div>
  <div class="stat-item">
    <span>VIDEOS ANALYZED</span>
    <span class="val" id="totalAnalyzed">0</span>
  </div>
  <div class="stat-sep"></div>
  <div class="stat-item">
    <span>ACCIDENTS DETECTED</span>
    <span class="val" id="totalAccidents" style="color:var(--red)">0</span>
  </div>
  <div class="stat-sep"></div>
  <div class="stat-item">
    <span>ALERTS SENT</span>
    <span class="val" id="totalAlerts" style="color:var(--yellow)">0</span>
  </div>
  <div class="time-display" id="clockDisplay">--:--:--</div>
</div>

<!-- MAIN LAYOUT -->
<div class="main">

  <!-- LEFT PANEL -->
  <div class="left-panel">
    <div class="panel-header">
      <span class="panel-label">Video Monitoring Feed</span>
      <span class="panel-tag" id="videoTag">NO FEED</span>
    </div>

    <!-- VIDEO BOX -->
    <div class="video-container" id="videoContainer" onclick="triggerUpload()">
      <div class="upload-placeholder" id="uploadPlaceholder">
        <div class="upload-icon">
          <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#888" stroke-width="1.5">
            <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
            <polyline points="17 8 12 3 7 8"/>
            <line x1="12" y1="3" x2="12" y2="15"/>
          </svg>
        </div>
        <p>Click to import footage</p>
        <small>Supports MP4, AVI, MOV, MKV</small>
      </div>
      <video id="mainVideo" controls></video>
      <div class="video-overlay" id="videoOverlay">
        <span class="video-filename" id="videoFilename">—</span>
        <span class="video-status-tag analyzing" id="videoStatusTag">ANALYZING</span>
      </div>
    </div>

    <!-- CONFIDENCE ROW -->
    <div class="confidence-row" id="confidenceRow">
      <span class="conf-label">MODEL CONFIDENCE</span>
      <div class="conf-bar-wrap">
        <div class="conf-bar-fill" id="confBarFill"></div>
      </div>
      <span class="conf-val" id="confVal">—</span>
    </div>

    <!-- DETECTION LOG -->
    <div class="detection-log">
      <div class="log-header">
        <div class="dot"></div>
        DETECTION LOG
      </div>
      <div class="log-body" id="logBody">
        <div class="log-entry info">
          <span class="ts" id="initTs">--:--:--</span>
          <span class="msg">System initialized. Awaiting video input.</span>
        </div>
      </div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div class="right-panel">

    <!-- Detection Status -->
    <div class="rp-section">
      <div class="rp-title">Detection Status</div>
      <div class="status-card">
        <div class="status-icon idle" id="statusIcon">🎯</div>
        <div class="status-label idle" id="statusLabel">SYSTEM IDLE</div>
        <div class="status-sub" id="statusSub">Upload a video to begin analysis</div>
      </div>
    </div>

    <!-- Stats -->
    <div class="rp-section">
      <div class="rp-title">Session Statistics</div>
      <div class="stats-grid">
        <div class="stat-box">
          <div class="s-label">ANALYZED</div>
          <div class="s-val" id="s_analyzed">0</div>
        </div>
        <div class="stat-box">
          <div class="s-label">ACCIDENTS</div>
          <div class="s-val red" id="s_accidents">0</div>
        </div>
        <div class="stat-box">
          <div class="s-label">ALERTS SENT</div>
          <div class="s-val" style="color:var(--yellow)" id="s_alerts">0</div>
        </div>
        <div class="stat-box">
          <div class="s-label">LAST RESULT</div>
          <div class="s-val" id="s_last" style="font-size:13px; margin-top:4px; color:var(--muted)">—</div>
        </div>
      </div>
    </div>

    <!-- Emergency Response -->
    <div class="emergency-section">
      <div class="rp-title">Emergency Response</div>

      <div class="idle-notice" id="idleNotice">
        <p>No active detection.<br>Import a video to begin monitoring.</p>
      </div>

      <div class="question-box" id="questionBox">
        <span class="q-label">⚠ ACCIDENT DETECTED</span>
        <p>The model has flagged potential accident activity. Do you want to dispatch an emergency alert?</p>
        <div class="btn-row">
          <button class="btn-yes" onclick="showAlertPopup()">YES — Alert Authorities</button>
          <button class="btn-no" onclick="dismissAlert()">NO — False Alarm</button>
        </div>
      </div>
    </div>

    <!-- Operator Info -->
    <div class="rp-section">
      <div class="rp-title">Operator Info</div>
      <div class="info-row">
        <span class="key">OPERATOR</span>
        <span class="val" id="infoUsername">—</span>
      </div>
      <div class="info-row">
        <span class="key">LOCATION</span>
        <span class="val" id="infoLocation" style="font-size:12px;">—</span>
      </div>
      <div class="info-row">
        <span class="key">STATUS</span>
        <span class="val" style="color:var(--green)">ON DUTY</span>
      </div>
    </div>

    <!-- Upload Action -->
    <div class="rp-section">
      <div class="rp-title">Actions</div>
      <button class="btn-upload-vid" onclick="triggerUpload()">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
          <polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/>
        </svg>
        Import New Video
      </button>
      <input type="file" id="fileInput" accept="video/*" onchange="handleFileSelect(event)" />
    </div>

  </div>
</div>

<!-- ALERT POPUP OVERLAY -->
<div class="popup-overlay" id="alertOverlay">
  <div class="popup">
    <div class="popup-alert-badge">
      <div class="alert-icon-big">🚨</div>
      <div>
        <h2>Accident Detected</h2>
        <p>Dispatch emergency alert?</p>
      </div>
    </div>

    <div class="popup-detail">
      <div class="popup-detail-row">
        <span class="k">OPERATOR</span>
        <span class="v" id="popupOperator">—</span>
      </div>
      <div class="popup-detail-row">
        <span class="k">LOCATION</span>
        <span class="v" id="popupLocation">—</span>
      </div>
      <div class="popup-detail-row">
        <span class="k">CONFIDENCE</span>
        <span class="v red" id="popupConf">—</span>
      </div>
      <div class="popup-detail-row">
        <span class="k">VIDEO FILE</span>
        <span class="v" id="popupFile" style="font-size:11px; max-width:200px; overflow:hidden; text-overflow:ellipsis;">—</span>
      </div>
    </div>

    <div class="popup-timer">
      Auto-cancel in <span id="timerCount">29</span>s
    </div>

    <div class="popup-actions">
      <button class="btn-alert-yes" onclick="confirmAlert()">🚨 ALERT AUTHORITIES</button>
      <button class="btn-alert-cancel" onclick="cancelAlert()">CANCEL</button>
    </div>
  </div>
</div>

<!-- SUCCESS POPUP OVERLAY -->
<div class="popup-overlay" id="successOverlay">
  <div class="success-popup">
    <div class="success-icon">✅</div>
    <h2>Authorities Alerted</h2>
    <p>Emergency services have been notified.<br>
    Location: <strong id="successLocation">—</strong><br>
    An SMS alert has been dispatched to the response team.</p>
    <button class="btn-dismiss" onclick="dismissSuccess()">ACKNOWLEDGE & CONTINUE</button>
  </div>
</div>

<script>

  // GLOBAL VARIABLES
  
  let sessionSeconds = 0;
  let totalAnalyzed = 0, totalAccidents = 0, totalAlerts = 0;
  let currentConfidence = 0, currentFile = '';
  let popupTimer = null, timerSeconds = 29;

  const OPERATOR_NAME = '<?php echo isset($_SESSION["username"]) ? htmlspecialchars($_SESSION["username"]) : "Operator" ?>';
  const OPERATOR_LOCATION = '<?php echo isset($_SESSION["location"]) ? htmlspecialchars($_SESSION["location"]) : "Unknown Location" ?>';

  // INITIALIZATION
  
  document.getElementById('navUsername').textContent = OPERATOR_NAME;
  document.getElementById('navLocation').textContent = OPERATOR_LOCATION;
  document.getElementById('infoUsername').textContent = OPERATOR_NAME;
  document.getElementById('infoLocation').textContent = OPERATOR_LOCATION;
  document.getElementById('popupOperator').textContent = OPERATOR_NAME;
  document.getElementById('popupLocation').textContent = OPERATOR_LOCATION;
  document.getElementById('successLocation').textContent = OPERATOR_LOCATION;
  document.getElementById('initTs').textContent = nowTime();

  // UTILITY FUNCTIONS

  function nowTime() {
    return new Date().toLocaleTimeString('en-GB');
  }

  function tick() {
    document.getElementById('clockDisplay').textContent = nowTime();
    sessionSeconds++;
    const h = String(Math.floor(sessionSeconds/3600)).padStart(2,'0');
    const m = String(Math.floor((sessionSeconds%3600)/60)).padStart(2,'0');
    const s = String(sessionSeconds%60).padStart(2,'0');
    document.getElementById('sessionTime').textContent = `${h}:${m}:${s}`;
  }
  setInterval(tick, 1000);

  function addLog(msg, type='info') {
    const lb = document.getElementById('logBody');
    const entry = document.createElement('div');
    entry.className = `log-entry ${type}`;
    entry.innerHTML = `<span class="ts">${nowTime()}</span><span class="msg">${msg}</span>`;
    lb.appendChild(entry);
    lb.scrollTop = lb.scrollHeight;
  }

  // VIDEO UPLOAD & ANALYSIS

  function triggerUpload() {
    document.getElementById('fileInput').click();
  }

  function handleFileSelect(e) {
    const file = e.target.files[0];
    if (!file) return;

    currentFile = file.name;

    const video = document.getElementById('mainVideo');
    const url = URL.createObjectURL(file);
    video.src = url;
    video.style.display = 'block';
    document.getElementById('uploadPlaceholder').style.display = 'none';
    document.getElementById('videoOverlay').style.display = 'flex';
    document.getElementById('videoFilename').textContent = file.name;
    document.getElementById('videoContainer').classList.add('has-video');
    document.getElementById('videoTag').textContent = 'PROCESSING';
    video.play();

    setStatus('running', '⏳', 'ANALYZING...', 'Model is processing the footage');
    document.getElementById('videoStatusTag').className = 'video-status-tag analyzing';
    document.getElementById('videoStatusTag').textContent = 'ANALYZING';
    document.getElementById('confidenceRow').style.display = 'flex';
    document.getElementById('confBarFill').style.width = '0%';
    document.getElementById('confVal').textContent = '—';
    document.getElementById('idleNotice').style.display = 'none';
    document.getElementById('questionBox').style.display = 'none';
    document.getElementById('popupFile').textContent = file.name;

    addLog(`Video loaded: ${file.name}`, 'info');
    addLog('Uploading to server for analysis...', 'warn');

    uploadAndAnalyze(file);
  }

  function uploadAndAnalyze(file) {
    const formData = new FormData();
    formData.append('video', file);

    fetch('php/analyze.php', {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => handleResult(data))
    .catch(err => {
      addLog('Server error: ' + err.message, 'danger');
      addLog('Falling back to simulation mode...', 'warn');
      setTimeout(() => simulateResult(), 2000);
    });
  }

  function simulateResult() {
    const isAccident = Math.random() > 0.5;
    const confidence = isAccident
      ? (Math.random() * 0.25 + 0.72).toFixed(2)
      : (Math.random() * 0.35 + 0.20).toFixed(2);
    handleResult({ accident: isAccident, confidence: parseFloat(confidence) });
  }

  function handleResult(data) {
    totalAnalyzed++;
    document.getElementById('totalAnalyzed').textContent = totalAnalyzed;
    document.getElementById('s_analyzed').textContent = totalAnalyzed;

    currentConfidence = data.confidence;
    const pct = Math.round(data.confidence * 100);

    setTimeout(() => {
      const fill = document.getElementById('confBarFill');
      fill.style.width = pct + '%';
      if (data.accident) fill.classList.add('danger');
      else fill.classList.remove('danger');
      document.getElementById('confVal').textContent = pct + '%';
    }, 200);

    document.getElementById('popupConf').textContent = pct + '%';

    if (data.accident) {
      totalAccidents++;
      document.getElementById('totalAccidents').textContent = totalAccidents;
      document.getElementById('s_accidents').textContent = totalAccidents;
      document.getElementById('s_last').textContent = 'ACCIDENT';
      document.getElementById('s_last').style.color = 'var(--red)';

      setStatus('danger', '🚨', 'ACCIDENT DETECTED', `Confidence: ${pct}%`);
      document.getElementById('videoStatusTag').className = 'video-status-tag danger';
      document.getElementById('videoStatusTag').textContent = 'ACCIDENT';
      document.getElementById('videoContainer').classList.add('accident-active');
      document.getElementById('videoTag').textContent = '⚠ ACCIDENT';

      document.getElementById('questionBox').style.display = 'block';
      addLog(`⚠ ACCIDENT DETECTED — Confidence: ${pct}%`, 'danger');
      addLog('Awaiting operator response...', 'warn');

    } else {
      document.getElementById('s_last').textContent = 'CLEAR';
      document.getElementById('s_last').style.color = 'var(--green)';

      setStatus('safe', '✅', 'NO ACCIDENT', `Confidence: ${pct}%`);
      document.getElementById('videoStatusTag').className = 'video-status-tag safe';
      document.getElementById('videoStatusTag').textContent = 'CLEAR';
      document.getElementById('videoTag').textContent = '✓ CLEAR';
      document.getElementById('idleNotice').style.display = 'block';
      addLog(`Analysis complete — No accident detected (${pct}% confidence)`, 'ok');
    }
  }

  function setStatus(type, icon, label, sub) {
    document.getElementById('statusIcon').className = `status-icon ${type}`;
    document.getElementById('statusIcon').textContent = icon;
    document.getElementById('statusLabel').className = `status-label ${type}`;
    document.getElementById('statusLabel').textContent = label;
    document.getElementById('statusSub').textContent = sub;
  }

  // ALERT POPUP FUNCTIONS

  function showAlertPopup() {
    document.getElementById('questionBox').style.display = 'none';
    document.getElementById('alertOverlay').classList.add('show');
    addLog('Alert popup opened. Countdown started.', 'warn');
    startTimer();
  }

  function dismissAlert() {
    document.getElementById('questionBox').style.display = 'none';
    document.getElementById('idleNotice').style.display = 'block';
    addLog('Operator dismissed alert. No SMS sent.', 'info');
  }

  function startTimer() {
    timerSeconds = 29;
    document.getElementById('timerCount').textContent = timerSeconds;
    
    popupTimer = setInterval(() => {
      timerSeconds--;
      document.getElementById('timerCount').textContent = timerSeconds;
      
      if (timerSeconds <= 0) {
        cancelAlert();
      }
    }, 1000);
  }

  function cancelAlert() {
    clearInterval(popupTimer);
    document.getElementById('alertOverlay').classList.remove('show');
    document.getElementById('idleNotice').style.display = 'block';
    addLog('Alert cancelled by operator.', 'info');
  }

  function confirmAlert() {
    clearInterval(popupTimer);
    document.getElementById('alertOverlay').classList.remove('show');
    
    addLog('🚨 ALERT CONFIRMED — Sending SMS...', 'danger');

    const pct = Math.round(currentConfidence * 100);
    
    fetch('php/send_alert.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        location: OPERATOR_LOCATION,
        operator: OPERATOR_NAME,
        confidence: pct,
        video: currentFile
      })
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        totalAlerts++;
        document.getElementById('totalAlerts').textContent = totalAlerts;
        document.getElementById('s_alerts').textContent = totalAlerts;
        
        if (data.sms_sent) {
          addLog('✅ SMS alert sent successfully!', 'ok');
        } else {
          addLog('⚠ Alert logged but SMS failed: ' + (data.error_detail || 'Unknown error'), 'warn');
        }
        
        document.getElementById('successOverlay').classList.add('show');
      } else {
        addLog('❌ Alert failed: ' + (data.error || 'Unknown error'), 'danger');
      }
    })
    .catch(err => {
      addLog('❌ Network error: ' + err.message, 'danger');
    });
  }

  function dismissSuccess() {
    document.getElementById('successOverlay').classList.remove('show');
    document.getElementById('idleNotice').style.display = 'block';
    addLog('Alert workflow completed.', 'ok');
  }

</script>
</body>
</html>