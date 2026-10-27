<?php defined('APP_ROOT') || exit;
$toggle = static function (string $key, string $title, string $help) use ($s) {
    return '<label class="setting-row" style="cursor:pointer"><div><b>' . e($title) . '</b><small>' . e($help) . '</small></div>'
        . '<input type="hidden" name="_has_' . $key . '" value="1"><span class="switch"><input type="checkbox" name="' . $key . '" value="1"' . checked($s[$key] === '1') . '><span></span></span></label>';
};
$num = static fn (string $key, string $label, string $hint = '') => '<label class="field"><span>' . e($label) . '</span><input class="input" type="number" name="' . $key . '" value="' . e($s[$key]) . '">' . ($hint ? '<p class="hint">' . e($hint) . '</p>' : '') . '</label>';
?>
<div class="ph"><div><h1>Settings</h1><p>Changes apply immediately for every member.</p></div></div>

<div class="grid-2" style="align-items:start">
<div>
    <form class="panel" method="post" action="/admin/settings" id="access">
        <?= csrf_field() ?><input type="hidden" name="section" value="access">
        <div class="panel-h"><h2>Access & sign-up</h2><div class="actions"><button class="btn btn-dark btn-xs">Save</button></div></div>
        <?= $toggle('registration_open', 'Open registration', 'Allow new people to create accounts.') ?>
        <?= $toggle('email_verification', 'Require email verification', 'New members must confirm their email before using the app. Make sure email sending works first.') ?>
        <?= $toggle('maintenance_mode', 'Maintenance mode', 'Show a "be right back" page to members. Admins can still use the site.') ?>
        <div class="panel-b" style="border-top:1px solid var(--line)">
            <label class="field"><span>Maintenance message</span><input class="input" name="maintenance_message" value="<?= e($s['maintenance_message']) ?>"></label>
            <?= $num('min_age', 'Minimum age', '18 or higher.') ?>
        </div>
    </form>

    <form class="panel" method="post" action="/admin/settings" id="discover">
        <?= csrf_field() ?><input type="hidden" name="section" value="discover">
        <div class="panel-h"><h2>Discover & matching</h2><div class="actions"><button class="btn btn-dark btn-xs">Save</button></div></div>
        <?= $toggle('show_seed_profiles', 'Show seed profiles', 'Include team-created seed profiles in Discover.') ?>
        <?= $toggle('require_photo', 'Only show profiles with photos', 'Hide profiles that have no approved photo.') ?>
        <?= $toggle('photo_moderation', 'Review photos before they go live', 'New member photos wait in Photo review until approved.') ?>
        <div class="panel-b form-grid" style="border-top:1px solid var(--line)">
            <?= $num('daily_like_limit', 'Daily likes per member', '0 = unlimited.') ?>
            <?= $num('daily_superlike_limit', 'Daily Super Likes per member', '0 = unlimited.') ?>
            <?= $num('max_photos', 'Max photos per profile') ?>
            <?= $num('message_max_length', 'Max message length') ?>
        </div>
    </form>

    <form class="panel" method="post" action="/admin/settings" id="safety">
        <?= csrf_field() ?><input type="hidden" name="section" value="safety">
        <div class="panel-h"><h2>Message safety</h2><div class="actions"><button class="btn btn-dark btn-xs">Save</button></div></div>
        <div class="panel-b">
            <?= $num('auto_hide_reports', 'Auto-hide a profile after reports from this many different members', '0 turns it off. Hidden profiles stay hidden until you unhide them.') ?>
            <label class="field"><span>Flag messages containing (one per line)</span><textarea class="textarea mono" name="flag_keywords" style="min-height:200px"><?= e($s['flag_keywords']) ?></textarea>
            <p class="hint">Phone numbers, email addresses and links are always flagged. Flagged messages are still delivered and appear in Flagged messages for review.</p></label>
        </div>
    </form>
</div>
<div>
    <form class="panel" method="post" action="/admin/settings" id="brand">
        <?= csrf_field() ?><input type="hidden" name="section" value="brand">
        <div class="panel-h"><h2>Brand</h2><div class="actions"><button class="btn btn-dark btn-xs">Save</button></div></div>
        <div class="panel-b">
            <label class="field"><span>App name</span><input class="input" name="site_name" value="<?= e($s['site_name']) ?>"></label>
            <label class="field"><span>Tagline</span><input class="input" name="tagline" value="<?= e($s['tagline']) ?>"></label>
            <label class="field"><span>Support email</span><input class="input" type="email" name="support_email" value="<?= e($s['support_email']) ?>"></label>
        </div>
        <?= $toggle('pwa_prompt', 'Ask members to install the app', 'Show the "Get the app" popup to signed-in members on mobile.') ?>
    </form>

    <form class="panel" method="post" action="/admin/settings" id="announce">
        <?= csrf_field() ?><input type="hidden" name="section" value="announce">
        <div class="panel-h"><h2>Announcement banner</h2><div class="actions"><button class="btn btn-dark btn-xs">Save</button></div></div>
        <?= $toggle('announcement_active', 'Show banner', 'A short message across the top of the app for signed-in members.') ?>
        <div class="panel-b" style="border-top:1px solid var(--line)"><label class="field"><span>Message</span><input class="input" name="announcement" maxlength="160" value="<?= e($s['announcement']) ?>" placeholder="e.g. New: see who's active now in Likes"></label></div>
    </form>

    <form class="panel" method="post" action="/admin/settings" id="email">
        <?= csrf_field() ?><input type="hidden" name="section" value="email">
        <div class="panel-h"><h2>Email delivery</h2><div class="actions"><button class="btn btn-dark btn-xs">Save</button></div></div>
        <div class="panel-b">
            <div class="form-grid">
                <label class="field"><span>Send with</span><select class="select" name="mail_driver"><option value="mail"<?= selected($s['mail_driver'], 'mail') ?>>Server mail()</option><option value="smtp"<?= selected($s['mail_driver'], 'smtp') ?>>SMTP</option></select></label>
                <label class="field"><span>From name</span><input class="input" name="mail_from_name" value="<?= e($s['mail_from_name']) ?>"></label>
                <label class="field full"><span>From address</span><input class="input" type="email" name="mail_from_address" value="<?= e($s['mail_from_address']) ?>"></label>
                <label class="field"><span>SMTP host</span><input class="input" name="smtp_host" value="<?= e($s['smtp_host']) ?>" placeholder="smtp.gmail.com"></label>
                <?= $num('smtp_port', 'SMTP port') ?>
                <label class="field"><span>SMTP username</span><input class="input" name="smtp_user" value="<?= e($s['smtp_user']) ?>" autocomplete="off"></label>
                <label class="field"><span>SMTP password</span><input class="input" type="password" name="smtp_pass" placeholder="<?= $s['smtp_pass'] !== '' ? '•••••••• (saved)' : '' ?>" autocomplete="new-password"></label>
                <label class="field"><span>Encryption</span><select class="select" name="smtp_secure"><option value="tls"<?= selected($s['smtp_secure'], 'tls') ?>>STARTTLS (587)</option><option value="ssl"<?= selected($s['smtp_secure'], 'ssl') ?>>SSL (465)</option><option value="none"<?= selected($s['smtp_secure'], 'none') ?>>None</option></select></label>
            </div>
            <button class="btn btn-dark">Save email settings</button>
        </div>
    </form>
    <form class="panel" method="post" action="/admin/settings/test-email">
        <?= csrf_field() ?>
        <div class="panel-b row" style="flex-wrap:wrap">
            <input class="input" type="email" name="to" placeholder="Send a test to…" value="<?= e(App\Core\AdminAuth::admin()['email']) ?>" style="flex:1;min-width:200px">
            <button class="btn btn-outline">Send test email</button>
        </div>
    </form>
</div>
</div>
