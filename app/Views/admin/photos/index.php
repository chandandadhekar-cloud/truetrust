<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Photo review</h1><p><?= setting('photo_moderation') === '1' ? 'New member photos wait here until approved.' : 'Photo moderation is off. Turn it on in Settings to review photos before they go live.' ?></p></div></div>
<?php if ($rows): ?>
<div class="queue">
    <?php foreach ($rows as $r): ?>
        <div class="queue-item">
            <div class="pa"><img src="<?= photo_url((int) $r['id'], 'thumb') ?>" alt="" loading="lazy"></div>
            <div class="qi-b"><a class="link small" href="/admin/users/<?= (int) $r['user_id'] ?>"><?= e($r['first_name'] ?: $r['email']) ?></a><span class="spacer"></span><small class="muted"><?= time_ago($r['created_at']) ?></small></div>
            <form class="qi-b" method="post" action="/admin/photos/<?= (int) $r['id'] ?>" style="padding-top:0">
                <?= csrf_field() ?>
                <button class="btn btn-dark btn-xs" style="flex:1" name="action" value="approve">Approve</button>
                <button class="btn btn-danger btn-xs" style="flex:1" name="action" value="reject">Reject</button>
            </form>
        </div>
    <?php endforeach; ?>
</div>
<?php else: ?>
<div class="panel"><div class="empty" style="padding:50px"><div class="empty-art"><?= icon('image', 40) ?></div><h2>Queue is empty</h2><p>No photos waiting for review.</p></div></div>
<?php endif; ?>
