<?php defined('APP_ROOT') || exit; ?>
<div class="auth">
    <?= partial('partials/auth-bar', ['back' => '/login']) ?>
    <div class="auth-body">
        <?php if (!$valid): ?>
            <h1 class="auth-title">This link has expired</h1>
            <p class="auth-sub">Reset links work for one hour and only once.</p>
            <a href="/forgot-password" class="btn btn-primary btn-block">Send a new link</a>
        <?php else: ?>
            <h1 class="auth-title">Choose a new password</h1>
            <p class="auth-sub">You'll be signed out on every other device.</p>
            <form method="post" action="/reset-password/<?= e($token) ?>" data-submit-once>
                <?= csrf_field() ?>
                <label class="field">
                    <span>New password</span>
                    <div class="input-group">
                        <input class="input" type="password" name="password" autocomplete="new-password" minlength="8" required autofocus>
                        <button type="button" class="icon-btn" data-toggle-password aria-label="Show password"><?= icon('eye', 20) ?></button>
                    </div>
                    <?= error_for('password') ?: '<p class="hint">At least 8 characters.</p>' ?>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Save password</button>
            </form>
        <?php endif; ?>
    </div>
</div>
