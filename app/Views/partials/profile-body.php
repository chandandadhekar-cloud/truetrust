<?php defined('APP_ROOT') || exit;
// Renders a profile card array (see User::card). Prompts are interleaved with photos.
$photos = $card['photos'];
?>
<div class="pv-gallery">
    <div class="pv-track" data-gallery>
        <?php foreach ($photos as $src): ?><img src="<?= e($src) ?>" alt="<?= e($card['name']) ?>"><?php endforeach; ?>
    </div>
    <?php if (count($photos) > 1): ?>
        <div class="pv-dots" data-gallery-dots><?php foreach ($photos as $i => $_): ?><i class="<?= $i === 0 ? 'on' : '' ?>"></i><?php endforeach; ?></div>
    <?php endif; ?>
</div>
<div class="pv-head">
    <div class="pv-name"><?= e($card['name']) ?> <span class="age"><?= (int) $card['age'] ?></span><?php if ($card['verified']): ?><?= icon('verified', 24) ?><?php endif; ?></div>
    <div class="pv-meta">
        <?php if ($card['activity']): ?><span style="color:<?= $card['online'] ? 'var(--ok)' : 'inherit' ?>"><?= icon('zap') ?><?= e($card['activity']) ?></span><?php endif; ?>
        <?php if ($card['job']): ?><span><?= icon('briefcase') ?><?= e($card['job']) ?></span><?php endif; ?>
        <?php if ($card['education']): ?><span><?= icon('cap') ?><?= e($card['education']) ?></span><?php endif; ?>
        <span><?= icon('pin') ?><?= e($card['city']) ?><?= $card['distance'] ? ' · ' . e($card['distance']) : '' ?></span>
    </div>
</div>
<?php if ($card['bio'] !== ''): ?>
    <div class="pv-section"><h3>About</h3><p class="pv-bio"><?= e($card['bio']) ?></p></div>
<?php endif; ?>
<?php if ($card['prompts']): ?><div style="height:14px"></div><?php endif; ?>
<?php foreach ($card['prompts'] as $p): ?>
    <div class="prompt-card"><div class="q"><?= e($p['q']) ?></div><div class="a"><?= e($p['a']) ?></div></div>
<?php endforeach; ?>
<?php if ($card['basics']): ?>
    <div class="pv-section"><h3>Basics</h3><div class="chips">
        <?php foreach ($card['basics'] as $b): ?><span class="chip"><?= icon($b['icon'], 16) ?><?= e($b['label']) ?></span><?php endforeach; ?>
    </div></div>
<?php endif; ?>
<?php if ($card['interests']): ?>
    <div class="pv-section"><h3>Interests</h3><div class="chips">
        <?php foreach ($card['interests'] as $i): ?><span class="chip"><?= e($i) ?></span><?php endforeach; ?>
    </div></div>
<?php endif; ?>
