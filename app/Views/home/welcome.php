<?php defined('APP_ROOT') || exit; ?>
<div class="welcome">
    <div class="welcome-top">
        <span class="brand"><?= partial('partials/logo') ?><span class="brand-word"><?= e(setting('site_name')) ?></span></span>
        <a href="/login" class="btn btn-ghost btn-sm">Sign in</a>
    </div>

    <div class="welcome-hero">
        <div class="stack-preview" aria-hidden="true">
            <div class="sp sp-1"><span>Ananya, 26<i>Pune</i></span></div>
            <div class="sp sp-2"><span>Kabir, 28<i>Bengaluru</i></span></div>
            <div class="sp sp-3"><span>Meera, 25<i>Kochi</i></span></div>
            <div class="sp-heart"><?= icon('heart', 24) ?></div>
        </div>
        <h1>Dating,<br>minus the <em>paywall.</em></h1>
        <p class="lede">See who likes you, send unlimited messages and meet people across India. Everything is free, for everyone, always.</p>

        <div class="welcome-points">
            <div class="welcome-point">
                <div class="pt-ico"><?= icon('heart', 20) ?></div>
                <div><b>See every like</b><span>No blurred faces. No gold tier.</span></div>
            </div>
            <div class="welcome-point">
                <div class="pt-ico"><?= icon('chat', 20) ?></div>
                <div><b>Chat without limits</b><span>Match, then talk as much as you want.</span></div>
            </div>
            <div class="welcome-point">
                <div class="pt-ico"><?= icon('shield', 20) ?></div>
                <div><b>Moderated for safety</b><span>Reports are reviewed by real people.</span></div>
            </div>
        </div>
    </div>

    <div class="welcome-cta">
        <a href="/signup" class="btn btn-primary btn-block">Create your free account</a>
        <a href="/login" class="btn btn-outline btn-block">I already have an account</a>
        <p class="welcome-legal">By continuing you agree to our <a href="/terms">Terms</a> and <a href="/privacy">Privacy Policy</a>. 18+ only.</p>
    </div>
</div>
