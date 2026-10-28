<?php defined('APP_ROOT') || exit;
$interests = App\Models\User::interests($other);
$prompts = App\Models\User::prompts($other);
$ice = ['Hi ' . $other['first_name'] . '! How has your week been?'];
if ($prompts) {
    $ice[] = 'Okay, I have to ask about "' . mb_strtolower($prompts[0]['q']) . '"';
}
if ($interests) {
    $ice[] = 'You\'re into ' . mb_strtolower($interests[0]) . ' too? Tell me more';
}
$ice[] = 'Chai or coffee? Choose wisely.';
$online = is_online($other['last_active_at']);
?>
<div class="chat" id="chat" data-match="<?= (int) $match['id'] ?>" data-ended="<?= $ended ? '1' : '0' ?>">
    <header class="chat-head">
        <a href="/matches" class="icon-btn" aria-label="Back"><?= icon('back', 24) ?></a>
        <a href="/u/<?= (int) $other['id'] ?>" class="avatar<?= $online ? ' is-online' : '' ?>"><?php if ($photo): ?><img src="<?= photo_url($photo, 'thumb') ?>" alt=""><?php else: ?><?= e(initials($other['first_name'])) ?><?php endif; ?></a>
        <a href="/u/<?= (int) $other['id'] ?>" class="who">
            <b><?= e($other['first_name']) ?><?php if ($other['is_verified']): ?><?= icon('verified', 16) ?><?php endif; ?></b>
            <small class="<?= $online ? 'on' : '' ?>"><?= e(activity_label($other['last_active_at']) ?: 'Matched ' . time_ago($match['created_at']) . ' ago') ?></small>
        </a>
        <button class="icon-btn" data-chat-menu aria-label="More options"><?= icon('more', 24) ?></button>
    </header>

    <div class="chat-scroll" id="chat-scroll">
        <div class="chat-intro" data-chat-intro>
            <a href="/u/<?= (int) $other['id'] ?>" class="avatar"><?php if ($photo): ?><img src="<?= photo_url($photo, 'thumb') ?>" alt=""><?php else: ?><?= e(initials($other['first_name'])) ?><?php endif; ?></a>
            <h3>You matched with <?= e($other['first_name']) ?></h3>
            <p><?= e(date('j F Y', strtotime($match['created_at']))) ?></p>
            <?php if (App\Models\Photo::aiUsers([(int) $other['id']])): ?><p class="ai-note" style="justify-content:center"><?= icon('sparkle', 15) ?>This profile's photos are AI-generated.</p><?php endif; ?>
            <div class="icebreakers" data-icebreakers>
                <?php foreach (array_slice($ice, 0, 3) as $line): ?><button type="button"><?= e($line) ?></button><?php endforeach; ?>
            </div>
        </div>
        <div class="safety-tip"><?= icon('shield', 18) ?><span>Keep chats on <?= e(setting('site_name')) ?> until you trust someone. Never send money or share OTPs.</span></div>
        <div id="chat-messages"></div>
    </div>

    <?php if ($ended): ?>
        <div class="chat-ended">This match has ended.</div>
    <?php else: ?>
        <form class="chat-compose" data-compose>
            <textarea rows="1" name="body" placeholder="Type a message" maxlength="<?= (int) setting('message_max_length') ?>" aria-label="Message"></textarea>
            <button class="send-btn" type="submit" disabled aria-label="Send"><?= icon('send', 20) ?></button>
        </form>
    <?php endif; ?>
</div>
<script type="application/json" id="chat-data"><?= json_encode(['messages' => $messages, 'other' => ['id' => (int) $other['id'], 'name' => $other['first_name']]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>

<template id="tpl-chat-menu">
    <div class="sheet-grip"></div>
    <div class="group">
        <a class="item" href="/u/<?= (int) $other['id'] ?>"><?= icon('user') ?>View <?= e($other['first_name']) ?>'s profile</a>
        <?php if (!$ended): ?>
        <form method="post" action="/chat/<?= (int) $match['id'] ?>/unmatch" data-confirm="Unmatch <?= e($other['first_name']) ?>? This can't be undone."><?= csrf_field() ?><button class="item"><?= icon('x') ?>Unmatch</button></form>
        <?php endif; ?>
        <button class="item item-danger" data-report data-id="<?= (int) $other['id'] ?>" data-name="<?= e($other['first_name']) ?>"><?= icon('flag') ?>Report</button>
        <button class="item item-danger" data-block data-id="<?= (int) $other['id'] ?>" data-name="<?= e($other['first_name']) ?>"><?= icon('block') ?>Block</button>
    </div>
    <button class="btn btn-ghost btn-block" data-sheet-close style="margin-top:10px">Cancel</button>
</template>
<?= partial('partials/report-sheet') ?>
