<?php defined('APP_ROOT') || exit; ?>
<div class="auth">
    <?= partial('partials/auth-bar', ['back' => '/login']) ?>
    <div class="auth-body">
        <h1 class="auth-title">Forgot your password?</h1>
        <p class="auth-sub">Enter the email you signed up with and we'll send you a link to choose a new one.</p>
        <form method="post" action="/forgot-password" data-submit-once>
            <?= csrf_field() ?>
            <label class="field">
                <span>Email</span>
                <input class="input" type="email" name="email" autocomplete="email" inputmode="email" required autofocus>
            </label>
            <button class="btn btn-primary btn-block" type="submit">Send reset link</button>
        </form>
    </div>
</div>
