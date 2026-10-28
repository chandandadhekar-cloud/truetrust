<?php defined('APP_ROOT') || exit; ?>
<header class="appbar"><div class="appbar-inner"><h1>Likes</h1></div></header>
<?= partial('partials/push-card') ?>
<?php if (!$people): ?>
    <div class="empty">
        <?= partial('partials/art', ['name' => 'likes']) ?>
        <h2>No likes yet</h2>
        <p>When someone likes you, they'll show up here. No blur, no paywall. A complete profile with three good photos helps.</p>
        <a href="/profile/edit" class="btn btn-dark btn-sm" style="margin-top:8px">Improve my profile</a>
    </div>
<?php else: ?>
    <div class="likes-hero">
        <div class="lh-num"><?= count($people) ?></div>
        <p><?= count($people) === 1 ? 'person likes' : 'people like' ?> you<?= $newCount ? ', ' . $newCount . ' new since your last visit' : '' ?>. Like them back to match instantly.</p>
    </div>
    <div class="likes-grid">
        <?php foreach ($people as $p): $pid = $photos[(int) $p['id']][0] ?? null; ?>
            <a class="like-tile" href="/u/<?= (int) $p['id'] ?>">
                <?php if ($pid): ?><img src="<?= photo_url($pid, 'thumb') ?>" alt="" loading="lazy"><?php endif; ?>
                <div class="lt-shade"></div>
                <?php if ($p['their_action'] === 'superlike'): ?>
                    <span class="lt-badge pill pill-gold"><?= icon('star', 13) ?> Super Like</span>
                <?php elseif (is_online($p['last_active_at'])): ?>
                    <span class="lt-badge pill pill-live">Active now</span>
                <?php endif; ?>
                <div class="lt-info">
                    <div class="lt-name"><?= e($p['first_name']) ?>, <?= age($p['dob']) ?><?php if ($p['is_verified']): ?> <?= icon('verified', 16) ?><?php endif; ?></div>
                    <div class="lt-sub"><?= e($p['city']) ?> · <?= time_ago($p['liked_at']) ?></div>
                    <?php if (isset($ai[(int) $p['id']])): ?><div class="lt-ai"><?= icon('sparkle', 12) ?>AI-generated photos</div><?php endif; ?>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
