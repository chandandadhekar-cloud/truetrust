<?php defined('APP_ROOT') || exit; ?>
<div class="auth">
    <?= partial('partials/auth-bar', ['back' => '/']) ?>
    <div class="auth-body">
        <h1 class="auth-title">Welcome back</h1>
        <p class="auth-sub">Sign in to see who's been liking you.</p>
        <form method="post" action="/login" data-submit-once novalidate>
            <?= csrf_field() ?>
            <label class="field">
                <span>Email</span>
                <input class="input<?= isset(errors()['email']) ? ' is-invalid' : '' ?>" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" inputmode="email" required autofocus>
                <?= error_for('email') ?>
            </label>
            <label class="field">
                <span>Password</span>
                <div class="input-group">
                    <input class="input" type="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="icon-btn" data-toggle-password aria-label="Show password"><?= icon('eye', 20) ?></button>
                </div>
            </label>
            <p style="text-align:right;margin:-6px 0 22px"><a href="/forgot-password" class="link small">Forgot password?</a></p>
            <button class="btn btn-primary btn-block" type="submit">Sign in</button>
        </form>
        <p class="auth-foot">New here? <a href="/signup" class="link">Create a free account</a></p>
    </div>
</div>
