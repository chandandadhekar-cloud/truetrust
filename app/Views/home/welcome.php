<?php defined('APP_ROOT') || exit; ?>
<div class="welcome">
    <div class="welcome-top">
        <span class="brand"><?= partial('partials/logo') ?><?= wordmark() ?></span>
        <a href="/login" class="btn btn-ghost btn-sm">Sign in</a>
    </div>

    <div class="welcome-art">
            <div class="stack-preview" aria-hidden="true">
            <div class="sp sp-1">
                <svg class="sp-art" viewBox="0 0 116 156" preserveAspectRatio="xMidYMid slice"><circle cx="58" cy="70" r="34" fill="#FFD9A8" opacity=".9"/><path d="M0 104h116v52H0z" fill="#C2304A" opacity=".35"/><path d="M-6 112c22-10 44-10 64 0s44 10 64 0v44H-6z" fill="#B92D47" opacity=".55"/></svg>
                <span>Ananya, 26<i>Pune</i></span>
            </div>
            <div class="sp sp-2">
                <svg class="sp-art" viewBox="0 0 116 156" preserveAspectRatio="xMidYMid slice"><g fill="none" stroke="#A8D5C2" stroke-width="2" opacity=".55"><path d="M14 156V64a44 44 0 0 1 88 0v92"/><path d="M28 156V68a30 30 0 0 1 60 0v88"/><path d="M42 156V72a16 16 0 0 1 32 0v84"/></g></svg>
                <span>Kabir, 28<i>Bengaluru</i></span>
            </div>
            <div class="sp sp-3">
                <svg class="sp-art" viewBox="0 0 116 156" preserveAspectRatio="xMidYMid slice"><g fill="#F2A33A"><circle cx="86" cy="38" r="20" opacity=".85"/><circle cx="86" cy="38" r="30" opacity=".25"/><circle cx="86" cy="38" r="42" opacity=".12"/></g></svg>
                <span>Meera, 25<i>Kochi</i></span>
            </div>
            <div class="sp-seal"><?= partial('partials/logo') ?></div>
        </div>
    </div>

    <div class="welcome-hero">
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
                <div><b>Verified and moderated</b><span>Selfie checks, and real people review every report.</span></div>
            </div>
        </div>
    </div>

    <div class="welcome-cta">
        <a href="/signup" class="btn btn-primary btn-block">Create your free account</a>
        <a href="/login" class="btn btn-outline btn-block">I already have an account</a>
        <p class="welcome-legal">By continuing you agree to our <a href="/terms">Terms</a> and <a href="/privacy">Privacy Policy</a>. 18+ only.</p>
    </div>

    <footer class="welcome-foot">
        <span>&copy; <?= date('Y') ?> <?= e(setting('site_name')) ?> · Made in India</span>
        <nav><a href="/safety">Safety</a><a href="/guidelines">Guidelines</a><a href="/privacy">Privacy</a><a href="/terms">Terms</a></nav>
    </footer>
</div>
