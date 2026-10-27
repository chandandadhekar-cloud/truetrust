<?php defined('APP_ROOT') || exit; ?>
<div class="profile-view" data-profile="<?= (int) $card['id'] ?>" data-name="<?= e($card['name']) ?>">
    <header class="appbar" style="position:absolute;left:0;right:0;background:none;backdrop-filter:none;-webkit-backdrop-filter:none;z-index:5"><div class="appbar-inner">
        <a href="/likes" data-back class="icon-btn" style="background:rgba(0,0,0,.4);color:#fff" aria-label="Back"><?= icon('back', 24) ?></a>
    </div></header>
    <div style="max-width:560px;margin:0 auto;position:relative">
        <?php if ($theyLiked && !$match): ?>
            <div style="position:absolute;top:calc(14px + var(--safe-t));right:14px;z-index:6" class="pill <?= $theyLiked === 'superlike' ? 'pill-gold' : 'pill-brand' ?>"><?= icon($theyLiked === 'superlike' ? 'star' : 'heart', 13) ?> <?= $theyLiked === 'superlike' ? 'Super Liked you' : 'Likes you' ?></div>
        <?php endif; ?>
        <?= partial('partials/profile-body', ['card' => $card]) ?>
        <?php if ($self): ?>
        <div class="page"><div class="notice notice-brand"><?= icon('eye', 20) ?><span>This is how other people see your profile. <a class="link" href="/profile/edit">Edit it</a></span></div></div>
        <?php else: ?>
        <div class="pv-safety">
            <button type="button" data-report><?= icon('flag', 16) ?> Report</button>
            <button type="button" data-block><?= icon('block', 16) ?> Block</button>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php if (!$self): ?>
<div class="pv-actions">
    <?php if ($match): ?>
        <a href="/chat/<?= (int) $match['id'] ?>" class="btn btn-primary" style="min-width:220px"><?= icon('chat', 20) ?> Message <?= e($card['name']) ?></a>
    <?php elseif (!$mySwipe || $mySwipe === 'pass'): ?>
        <button class="act act-lg act-pass" data-swipe="pass" aria-label="Pass"><?= icon('x', 30) ?></button>
        <button class="act act-sm act-super" data-swipe="superlike" aria-label="Super Like" style="align-self:center"><?= icon('star', 22) ?></button>
        <button class="act act-lg act-like" data-swipe="like" aria-label="Like"><?= icon('heart', 30) ?></button>
    <?php else: ?>
        <span class="chip chip-brand" style="padding:12px 18px"><?= icon('heart', 16) ?> You liked <?= e($card['name']) ?></span>
    <?php endif; ?>
</div>
<?php endif; ?>
<?= partial('partials/report-sheet') ?>
