<?php defined('APP_ROOT') || exit; ?>
<div class="push-card hidden" data-push-card>
    <div class="pc-ico"><?= icon('bell', 20) ?></div>
    <div class="pc-text"><b>Never miss a match</b><span>Get notified when someone likes you or messages you.</span></div>
    <button type="button" class="btn btn-primary btn-sm" data-push-enable>Turn on</button>
    <button type="button" class="icon-btn pc-x" data-push-dismiss aria-label="Not now"><?= icon('x', 18) ?></button>
</div>
