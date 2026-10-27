<?php defined('APP_ROOT') || exit;
$pending = $latest && $latest['status'] === 'pending';
$rejected = $latest && $latest['status'] === 'rejected';
?>
<header class="appbar"><div class="appbar-inner">
    <a href="/profile" class="icon-btn" aria-label="Back"><?= icon('back', 24) ?></a>
    <span class="title-sm">Get verified</span>
</div></header>
<div class="page">
<?php if ($me['is_verified']): ?>
    <div class="empty" style="padding-top:20px">
        <?= partial('partials/art', ['name' => 'caught-up']) ?>
        <h2>You're verified</h2>
        <p>Your profile shows the blue badge, so people know you're really you.</p>
        <a href="/discover" class="btn btn-primary btn-sm" style="margin-top:8px">Back to Discover</a>
    </div>
<?php elseif ($pending): ?>
    <div class="empty" style="padding-top:20px">
        <?= partial('partials/art', ['name' => 'mail']) ?>
        <h2>We're checking your selfie</h2>
        <p>A real person compares it with your profile photos. This usually takes less than a day. We'll notify you.</p>
    </div>
<?php else: ?>
    <div class="verify-hero">
        <span class="vh-badge"><?= icon('verified', 34) ?></span>
        <h1 class="ob-title" style="margin:14px 0 6px">Show people you're real</h1>
        <p class="muted">Verified profiles get more matches and more replies. It takes 30 seconds.</p>
    </div>
    <?php if ($rejected): ?>
        <div class="notice" style="margin-bottom:16px"><?= icon('info', 20) ?><span>Your last selfie couldn't be approved<?= $latest['note'] !== '' ? ': ' . e($latest['note']) : '' ?>. Please try again with the new pose.</span></div>
    <?php endif; ?>
    <div class="pose-card">
        <small>Copy this pose</small>
        <p class="pose-text"><?= e($poseText) ?></p>
        <ul class="pose-tips">
            <li><?= icon('check', 16) ?>Face the camera in good light</li>
            <li><?= icon('check', 16) ?>Just you, no filters or sunglasses</li>
            <li><?= icon('check', 16) ?>Your face should match your profile photos</li>
        </ul>
    </div>
    <form method="post" action="/verify-profile" enctype="multipart/form-data" data-selfie-form>
        <?= csrf_field() ?>
        <label class="btn btn-primary btn-block" style="cursor:pointer;margin-top:18px">
            <?= icon('camera', 20) ?> <span data-selfie-label>Take my selfie</span>
            <input type="file" name="selfie" accept="image/*" capture="user" hidden data-selfie-input>
        </label>
    </form>
    <p class="center small muted" style="margin-top:14px">Your selfie is only seen by our moderators and is deleted after review.</p>
<?php endif; ?>
</div>
<script src="<?= asset('js/verify.js') ?>" defer></script>
