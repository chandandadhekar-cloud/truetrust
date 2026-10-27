<?php defined('APP_ROOT') || exit; ?>
<div class="auth">
    <?= partial('partials/auth-bar', ['back' => '/']) ?>
    <div class="auth-body">
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-sub">It takes about two minutes. No card, no trial, nothing to upgrade.</p>
        <?php if ($closed): ?>
            <div class="notice"><?= icon('info', 20) ?><span>New sign-ups are paused for a short while. Please check back soon.</span></div>
        <?php else: ?>
        <form method="post" action="/signup" data-submit-once novalidate>
            <?= csrf_field() ?>
            <label class="field">
                <span>Email</span>
                <input class="input<?= isset(errors()['email']) ? ' is-invalid' : '' ?>" type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email" inputmode="email" required autofocus>
                <?= error_for('email') ?>
            </label>
            <label class="field">
                <span>Password</span>
                <div class="input-group">
                    <input class="input<?= isset(errors()['password']) ? ' is-invalid' : '' ?>" type="password" name="password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="icon-btn" data-toggle-password aria-label="Show password"><?= icon('eye', 20) ?></button>
                </div>
                <?= error_for('password') ?: '<p class="hint">At least 8 characters.</p>' ?>
            </label>
            <label class="row" style="align-items:flex-start;gap:12px;margin:6px 0 22px;font-size:14px;color:var(--ink-2)">
                <span class="switch" style="margin-top:2px"><input type="checkbox" name="agree" value="1"<?= checked((bool) old('agree')) ?>><span></span></span>
                <span>I'm 18 or older and agree to the <a class="link" href="/terms" target="_blank">Terms</a>, <a class="link" href="/guidelines" target="_blank">Community Guidelines</a> and <a class="link" href="/privacy" target="_blank">Privacy Policy</a>.</span>
            </label>
            <?= error_for('agree') ?>
            <button class="btn btn-primary btn-block" type="submit">Continue</button>
        </form>
        <?php endif; ?>
        <p class="auth-foot">Already a member? <a href="/login" class="link">Sign in</a></p>
    </div>
</div>
