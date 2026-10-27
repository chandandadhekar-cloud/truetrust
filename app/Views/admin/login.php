<?php defined('APP_ROOT') || exit; $toast = App\Core\Session::pull('toast'); ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex, nofollow">
<title>Admin · <?= e(setting('site_name')) ?></title>
<link rel="icon" href="/assets/img/icon.svg" type="image/svg+xml">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,600&family=Manrope:wght@400;600;700;800&display=swap">
<link rel="stylesheet" href="<?= asset('css/app.css') ?>"><link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="admin">
<div class="login-wrap">
    <form class="login-card" method="post" action="/admin/login">
        <?= csrf_field() ?>
        <span class="brand" style="margin-bottom:22px"><?= partial('partials/logo') ?><?= wordmark() ?></span>
        <h1 class="display" style="font-size:26px;margin-bottom:4px">Admin sign in</h1>
        <p class="muted" style="margin-bottom:22px">Authorised team members only.</p>
        <?php if ($toast): ?><div class="notice" style="margin-bottom:16px"><?= icon('alert', 18) ?><span><?= e($toast['message']) ?></span></div><?php endif; ?>
        <label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?= e(old('email')) ?>" required autofocus autocomplete="username"></label>
        <label class="field"><span>Password</span><input class="input" type="password" name="password" required autocomplete="current-password"></label>
        <button class="btn btn-dark btn-block" style="margin-top:6px">Sign in</button>
    </form>
</div>
</body>
</html>
