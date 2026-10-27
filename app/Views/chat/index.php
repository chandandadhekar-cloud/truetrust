<?php defined('APP_ROOT') || exit; ?>
<header class="appbar"><div class="appbar-inner"><h1>Chats</h1></div></header>
<?php if (!$fresh && !$convos): ?>
    <div class="empty">
        <div class="empty-art"><?= icon('chat', 40) ?></div>
        <h2>No matches yet</h2>
        <p>When you and someone like each other, you'll be able to chat here. Keep swiping.</p>
        <a href="/discover" class="btn btn-primary btn-sm" style="margin-top:8px">Start discovering</a>
    </div>
<?php else: ?>
    <?php if ($fresh): ?>
        <p class="group-title" style="margin:6px 18px 12px">New matches</p>
        <div class="new-matches">
            <?php foreach ($fresh as $m): $o = $m['other']; ?>
                <a href="/chat/<?= $m['id'] ?>" class="nm<?= !$m['seen'] ? ' is-new' : '' ?>">
                    <div class="nm-ph"><?php if ($o['photo']): ?><img src="<?= e($o['photo']) ?>" alt="" loading="lazy"><?php else: ?><?= e(initials($o['name'])) ?><?php endif; ?></div>
                    <?= e($o['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p class="group-title" style="margin:6px 18px 8px">Messages</p>
    <?php if (!$convos): ?>
        <p class="muted" style="padding:6px 18px 20px">Say hi to your new matches. Asking about one of their prompts works well.</p>
    <?php endif; ?>
    <div class="convos">
        <?php foreach ($convos as $m): $o = $m['other']; $last = $m['last']; $mine = (int) $last['sender_id'] === (int) $me['id']; ?>
            <a href="/chat/<?= $m['id'] ?>" class="convo<?= $m['unread'] ? ' is-unread' : '' ?>">
                <span class="avatar<?= $o['online'] ? ' is-online' : '' ?>"><?php if ($o['photo']): ?><img src="<?= e($o['photo']) ?>" alt="" loading="lazy"><?php else: ?><?= e(initials($o['name'])) ?><?php endif; ?></span>
                <div class="convo-body">
                    <div class="convo-top">
                        <span class="convo-name"><?= e($o['name']) ?><?php if ($o['verified']): ?><?= icon('verified', 15) ?><?php endif; ?></span>
                        <?php if (!$mine && !$m['unread']): ?><span class="your-turn">Your turn</span><?php endif; ?>
                        <span class="convo-time"><?= time_ago($last['created_at']) ?></span>
                    </div>
                    <div class="row" style="gap:8px">
                        <div class="convo-last" style="flex:1"><?= $mine ? 'You: ' : '' ?><?= $last['is_removed'] ? '<i>Message removed</i>' : e(mb_strimwidth($last['body'], 0, 90, '…')) ?></div>
                        <?php if ($m['unread']): ?><span class="convo-unread"><?= $m['unread'] ?></span><?php endif; ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
