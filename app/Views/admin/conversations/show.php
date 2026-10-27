<?php defined('APP_ROOT') || exit;
$ban = static function (array $u) {
    if ($u['status'] !== 'active') {
        return '<span class="tag tag-bad">' . e(ucfirst($u['status'])) . '</span>';
    }
    return '<form method="post" action="/admin/users/' . (int) $u['id'] . '/action" data-confirm="Ban ' . e($u['first_name']) . '?">' . csrf_field()
        . '<input type="hidden" name="action" value="ban"><input type="hidden" name="reason" value="Violation found in conversation review">'
        . '<button class="btn btn-danger btn-xs">Ban ' . e($u['first_name']) . '</button></form>';
};
?>
<div class="ph">
    <div><h1><?= e($a['first_name']) ?> &amp; <?= e($b['first_name']) ?></h1><p>Matched <?= date('j M Y, g:i A', strtotime($m['created_at'])) ?><?= $m['unmatched_at'] ? ' · Unmatched ' . date('j M Y', strtotime($m['unmatched_at'])) : '' ?> · <?= count($messages) ?> messages</p></div>
    <div class="actions"><a href="/admin/conversations" class="btn btn-ghost"><?= icon('back', 18) ?> Conversations</a></div>
</div>
<div class="grid-side">
    <div class="panel">
        <div class="thread">
            <?php $day = null; foreach ($messages as $msg): $left = (int) $msg['sender_id'] === (int) $a['id'];
                $d = date('Y-m-d', strtotime($msg['created_at'])); ?>
                <?php if ($d !== $day): $day = $d; ?><div class="msg-day"><?= date('j M Y', strtotime($d)) ?></div><?php endif; ?>
                <div class="msg <?= $left ? 'left' : 'right' ?><?= $msg['is_flagged'] ? ' flagged' : '' ?><?= $msg['is_removed'] ? ' removed' : '' ?>">
                    <div class="bubble"><?= e($msg['body']) ?></div>
                    <div class="meta">
                        <b><?= e($left ? $a['first_name'] : $b['first_name']) ?></b>
                        <span><?= date('g:i A', strtotime($msg['created_at'])) ?></span>
                        <?php if ($msg['read_at']): ?><span>Seen</span><?php endif; ?>
                        <?php if ($msg['is_flagged']): ?><span class="tag tag-warn"><?= e($msg['flag_reason']) ?></span><?php endif; ?>
                        <form method="post" action="/admin/messages/<?= (int) $msg['id'] ?>"><?= csrf_field() ?>
                            <?php if ($msg['is_removed']): ?><button name="action" value="restore">Restore</button><?php else: ?><button name="action" value="remove">Remove</button><?php endif; ?>
                            <?php if ($msg['is_flagged']): ?> · <button name="action" value="clear">Mark reviewed</button><?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$messages): ?><p class="muted center" style="padding:30px">No messages yet.</p><?php endif; ?>
        </div>
    </div>
    <div>
        <?php foreach ([[$a, $photoA], [$b, $photoB]] as [$u, $ph]): ?>
            <div class="panel">
                <div class="panel-b">
                    <?= partial('admin/partials/who', ['u' => $u, 'photoId' => $ph]) ?>
                    <p class="small muted" style="margin:10px 0"><?= $u['dob'] ? age($u['dob']) . ' · ' : '' ?><?= e($u['city']) ?><?= $u['is_seed'] ? ' · Seed profile' : '' ?></p>
                    <div class="row" style="flex-wrap:wrap;gap:8px">
                        <a class="btn btn-outline btn-xs" href="/admin/users/<?= (int) $u['id'] ?>">Profile</a>
                        <?= $ban($u) ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
