<?php defined('APP_ROOT') || exit; /* expects $u with id, first_name, last_name, email, is_verified, is_seed; optional $photoId */ ?>
<a class="who" href="/admin/users/<?= (int) $u['id'] ?>">
    <span class="avatar"><?php if (!empty($photoId)): ?><img src="<?= photo_url((int) $photoId, 'thumb') ?>" alt="" loading="lazy"><?php else: ?><?= e(initials($u['first_name'] ?: $u['email'])) ?><?php endif; ?></span>
    <span><b><?= e(trim($u['first_name'] . ' ' . ($u['last_name'] ?? '')) ?: 'Unnamed') ?><?php if (!empty($u['is_verified'])): ?><?= icon('verified', 15) ?><?php endif; ?></b><small><?= e($u['email']) ?></small></span>
</a>
