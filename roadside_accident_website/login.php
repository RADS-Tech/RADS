<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>RADS - Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Barlow:wght@300;400;600;700&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
      --bg: #0d0d0d; --panel: #161616; --border: #2a2a2a;
      --text: #e0e0e0; --muted: #666; --red: #e53935;
      --red-dim: #7a1f1d; --green: #43a047; --yellow: #fbc02d;
      --mono: 'Share Tech Mono', monospace; --sans: 'Barlow', sans-serif;
    }
    body {
      background: var(--bg); color: var(--text); font-family: var(--sans);
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
    }
    body::before {
      content: ''; position: fixed; inset: 0;
      background-image:
        linear-gradient(rgba(229,57,53,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(229,57,53,0.04) 1px, transparent 1px);
      background-size: 40px 40px; pointer-events: none; z-index: 0;
    }
    .wrapper {
      position: relative; z-index: 1;
      width: 100%; max-width: 420px; padding: 20px;
      animation: fadeUp 0.6s ease both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .logo-bar { text-align: center; margin-bottom: 32px; }
    .badge {
      display: inline-flex; align-items: center; gap: 10px;
      background: rgba(229,57,53,0.1); border: 1px solid var(--red-dim);
      padding: 6px 16px; border-radius: 2px; margin-bottom: 14px;
    }
    .pulse-dot {
      width: 8px; height: 8px; border-radius: 50%; background: var(--red);
      animation: pulse 1.5s ease-in-out infinite;
    }
    @keyframes pulse {
      0%,100% { opacity: 1; transform: scale(1); }
      50% { opacity: 0.4; transform: scale(0.7); }
    }
    .badge span { font-family: var(--mono); font-size: 11px; letter-spacing: 2px; color: var(--red); }
    .logo-bar h1 { font-size: 28px; font-weight: 700; letter-spacing: 1px; color: #fff; }
    .logo-bar h1 span { color: var(--red); }
    .logo-bar p { font-size: 13px; color: var(--muted); margin-top: 4px; font-family: var(--mono); letter-spacing: 1px; }

    .card {
      background: var(--panel); border: 1px solid var(--border);
      border-top: 2px solid var(--red); padding: 32px; position: relative;
    }
    .card::after {
      content: ''; position: absolute; top: 0; left: 0; right: 0;
      height: 1px; background: linear-gradient(90deg, transparent, var(--red), transparent);
    }
    .card-title {
      font-family: var(--mono); font-size: 12px; letter-spacing: 3px;
      color: var(--muted); text-transform: uppercase; margin-bottom: 24px;
      display: flex; align-items: center; gap: 10px;
    }
    .card-title::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    .field { margin-bottom: 16px; }
    label { display: block; font-size: 11px; font-family: var(--mono); letter-spacing: 1.5px; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; }
    input {
      width: 100%; background: #0f0f0f; border: 1px solid var(--border);
      color: var(--text); font-family: var(--sans); font-size: 14px;
      padding: 10px 12px; outline: none; transition: border-color 0.2s; border-radius: 2px;
    }
    input:focus { border-color: var(--red); box-shadow: 0 0 0 3px rgba(229,57,53,0.1); }
    input::placeholder { color: #333; }

    .btn-submit {
      width: 100%; background: var(--red); color: #fff; border: none;
      padding: 13px; font-family: var(--mono); font-size: 13px;
      letter-spacing: 2px; text-transform: uppercase; cursor: pointer;
      transition: background 0.2s; border-radius: 2px; margin-top: 4px;
    }
    .btn-submit:hover { background: #c62828; }

    .register-link { text-align: center; margin-top: 18px; font-size: 13px; color: var(--muted); }
    .register-link a { color: var(--red); text-decoration: none; font-weight: 600; }
    .register-link a:hover { text-decoration: underline; }

    .error-msg {
      background: rgba(229,57,53,0.1); border: 1px solid var(--red-dim);
      color: #ff6b6b; font-size: 13px; padding: 10px 14px; border-radius: 2px;
      margin-bottom: 16px; font-family: var(--mono);
    }

    /*scanline animation on card*/
    .card::before {
      content: ''; position: absolute; left: 0; right: 0; top: -100%;
      height: 100%; background: linear-gradient(transparent 50%, rgba(229,57,53,0.015) 50%);
      background-size: 100% 4px;
      animation: scanline 8s linear infinite; pointer-events: none; z-index: 0;
    }
    @keyframes scanline { to { top: 200%; } }
  </style>
</head>
<body>
  <div class="wrapper">
    <div class="logo-bar">
      <div class="badge">
        <div class="pulse-dot"></div>
        <span>SYSTEM ONLINE</span>
      </div>
      <h1>R<span>A</span>DS</h1>
      <p>Roadside Accident Detection System</p>
    </div>

    <div class="card">
      <div class="card-title">Operator Sign In</div>

      <?php if (isset($_GET['error'])): ?>
        <div class="error-msg">⚠ <?= htmlspecialchars($_GET['error']) ?></div>
      <?php endif; ?>

      <form method="POST" action="php/login.php">
        <div class="field">
          <label>Username</label>
          <input type="text" name="username" placeholder="operator_01" required autofocus />
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" placeholder="••••••••" required />
        </div>
        <button type="submit" class="btn-submit">Access System →</button>
      </form>

      <div class="register-link">
        New operator? <a href="register.html">Create Account</a>
      </div>
    </div>
  </div>
</body>
</html>
