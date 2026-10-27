<?php defined('APP_ROOT') || exit; ?>
<template id="tpl-install">
    <div class="sheet-grip"></div>
    <div class="install">
        <img class="install-icon" src="/assets/img/icon-192.png" alt="" width="64" height="64">
        <div>
            <h2 style="margin:0">Get the app</h2>
            <p class="muted small">Faster, full screen, and one tap away on your home screen. Free, no app store needed.</p>
        </div>
    </div>
    <div data-install="native">
        <button class="btn btn-primary btn-block" data-install-go><?= icon('download', 20) ?> Install <?= e(setting('site_name')) ?></button>
    </div>
    <div data-install="ios" class="hidden">
        <div class="install-steps">
            <div><b>1</b> Tap <?= icon('share', 20) ?> <span>Share in Safari's toolbar</span></div>
            <div><b>2</b> Choose <?= icon('add-square', 20) ?> <span>Add to Home Screen</span></div>
            <div><b>3</b> Tap <span><strong>Add</strong>, then open it from your home screen</span></div>
        </div>
    </div>
    <button class="btn btn-ghost btn-block" data-install-later style="margin-top:6px">Maybe later</button>
</template>
