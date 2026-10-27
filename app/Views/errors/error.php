<?php defined('APP_ROOT') || exit; ?>
<!doctype html>
<html lang="en">
<head><?= partial('partials/head', ['title' => $title]) ?></head>
<body>
<div class="error-page">
    <div class="code"><?= $status === 503 ? '' : (int) $status ?></div>
    <?php if ($status === 503): ?><div class="empty-art"><?= icon('refresh', 40) ?></div><?php endif; ?>
    <h1><?= e($title) ?></h1>
    <p><?= e($message ?: ($status === 404 ? "We couldn't find that page. It may have moved." : 'Please try again in a moment.')) ?></p>
    <a href="/" class="btn btn-primary" style="margin-top:12px">Go home</a>
</div>
</body>
</html>
