<?php
define('APP_ROOT', __DIR__);
require APP_ROOT . '/includes/db.php';

$status = db_status();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TrueTrust — Coming Soon</title>
    <meta name="description" content="TrueTrust is launching soon.">
    <style>
        :root {
            --bg: #0b1220;
            --card: rgba(255, 255, 255, 0.04);
            --border: rgba(255, 255, 255, 0.08);
            --text: #e6ebf5;
            --muted: #8b97ad;
            --accent: #4f8cff;
            --ok: #22c55e;
            --err: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 20% 20%, rgba(79, 140, 255, 0.18), transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(34, 197, 94, 0.10), transparent 45%),
                var(--bg);
        }
        .wrap { max-width: 560px; width: 100%; text-align: center; }
        .logo {
            display: inline-flex; align-items: center; gap: 10px;
            font-weight: 700; font-size: 1.25rem; letter-spacing: 0.02em; margin-bottom: 40px;
        }
        .logo-mark {
            width: 36px; height: 36px; border-radius: 10px;
            background: linear-gradient(135deg, var(--accent), #7c5cff);
            display: grid; place-items: center; font-size: 1rem; color: #fff;
        }
        h1 { font-size: clamp(2.25rem, 7vw, 3.5rem); line-height: 1.1; margin-bottom: 16px; }
        h1 span { color: var(--accent); }
        p.lead { color: var(--muted); font-size: 1.05rem; line-height: 1.6; margin-bottom: 40px; }
        .status {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 10px 16px; border-radius: 999px;
            background: var(--card); border: 1px solid var(--border);
            font-size: 0.9rem;
        }
        .dot { width: 10px; height: 10px; border-radius: 50%; }
        .status.ok .dot { background: var(--ok); box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2); }
        .status.err .dot { background: var(--err); box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.2); }
        .status small { color: var(--muted); }
        footer { margin-top: 56px; color: var(--muted); font-size: 0.8rem; }
    </style>
</head>
<body>
    <main class="wrap">
        <div class="logo"><div class="logo-mark">T</div>TrueTrust</div>
        <h1>Something <span>great</span> is coming soon.</h1>
        <p class="lead">We're working hard to launch our new website. Stay tuned — we'll be live shortly.</p>

        <div class="status <?= $status['ok'] ? 'ok' : 'err' ?>">
            <span class="dot"></span>
            <span><?= htmlspecialchars($status['message']) ?></span>
            <?php if ($status['version']): ?>
                <small>MySQL <?= htmlspecialchars($status['version']) ?></small>
            <?php endif; ?>
        </div>

        <footer>&copy; <?= date('Y') ?> TrueTrust. All rights reserved.</footer>
    </main>
</body>
</html>
