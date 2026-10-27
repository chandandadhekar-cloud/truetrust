<?php defined('APP_ROOT') || exit; ?>
<header class="appbar"><div class="appbar-inner">
    <a href="/settings" class="icon-btn" aria-label="Back"><?= icon('back', 24) ?></a>
    <span class="title-sm">Blocked people</span>
</div></header>
<div class="page">
    <?php if (!$people): ?>
        <div class="empty"><div class="empty-art"><?= icon('block', 40) ?></div><h2>Nobody blocked</h2><p>People you block or report appear here. You can unblock them any time.</p></div>
    <?php else: ?>
        <p class="muted small" style="margin:4px 4px 14px">Blocked people can't see you, like you or message you.</p>
        <div class="group">
            <?php foreach ($people as $p): $pid = $photos[(int) $p['id']][0] ?? null; ?>
                <div class="item">
                    <span class="avatar" style="width:44px;height:44px;font-size:16px"><?php if ($pid): ?><img src="<?= photo_url($pid, 'thumb') ?>" alt=""><?php else: ?><?= e(initials($p['first_name'])) ?><?php endif; ?></span>
                    <span style="flex:1"><?= e($p['first_name']) ?><?= $p['dob'] ? ', ' . age($p['dob']) : '' ?><br><small class="muted" style="font-weight:500"><?= e($p['city']) ?> · blocked <?= time_ago($p['created_at']) ?></small></span>
                    <form method="post" action="/settings/unblock/<?= (int) $p['id'] ?>" data-confirm="Unblock <?= e($p['first_name']) ?>?"><?= csrf_field() ?><button class="btn btn-outline btn-sm">Unblock</button></form>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
