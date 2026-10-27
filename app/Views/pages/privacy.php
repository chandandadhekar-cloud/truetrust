<?php defined('APP_ROOT') || exit; ?>
<?= partial('pages/_bar', ['title' => $title]) ?>
<article class="prose">
    <p class="updated">Last updated 28 October 2026</p>
    <p>This policy explains what <?= e(setting('site_name')) ?> collects, why, and the choices you have. We wrote it to be read, not skimmed past.</p>
    <h2>What we collect</h2>
    <ul>
        <li><b>Account details:</b> email address and a securely hashed password.</li>
        <li><b>Profile:</b> first name, age, gender, who you'd like to meet, city, photos, bio, prompts and any optional details you add.</li>
        <li><b>Activity:</b> likes, passes, matches, messages, reports and blocks.</li>
        <li><b>Technical:</b> IP address at sign-up, device and browser type, and when you were last active.</li>
    </ul>
    <h2>Location</h2>
    <p>We only store the city you choose. If you use "current location", your device's position is used once to find the nearest city and is not stored. Other members see your city and an approximate distance, never your exact location.</p>
    <h2>Photos</h2>
    <p>Every uploaded photo is re-processed, which removes hidden metadata such as GPS location. Photos are only visible to signed-in members.</p>
    <h2>Selfie verification</h2>
    <p>If you choose to get verified, you take a selfie copying a pose we show you. A moderator compares it with your profile photos. The selfie is only visible to authorised moderators and is deleted as soon as a decision is made. Only the result (verified or not) is kept.</p>
    <h2>Notifications</h2>
    <p>If you turn on notifications, your browser gives us a delivery address for your device. Notifications sent through Apple's or Google's push services carry no message content; your device fetches the details directly from us. You can turn each type off in Settings, or turn them off completely in your browser.</p>
    <h2>Messages and moderation</h2>
    <p>Your messages are visible to you and your match. To protect members from scams, harassment and abuse, authorised <?= e(setting('site_name')) ?> moderators can review conversations, especially when a message is reported or automatically flagged (for example, requests for money or payment details). We only do this to enforce our guidelines and keep the platform safe.</p>
    <h2>How we use your information</h2>
    <ul>
        <li>To show your profile to people who match your preferences and theirs.</li>
        <li>To deliver messages and notifications.</li>
        <li>To prevent fraud, spam and abuse, and to enforce our terms.</li>
        <li>To send account emails such as verification and password resets.</li>
    </ul>
    <p>We do not sell your personal data. We do not show ads based on your profile.</p>
    <h2>Who can see what</h2>
    <p>Other members can see your public profile: first name, age, city, photos, bio, prompts and details you choose to add. Your email, surname and exact birthday are never shown.</p>
    <h2>Your choices</h2>
    <ul>
        <li>Edit your profile any time.</li>
        <li>Pause your profile to stop appearing in Discover.</li>
        <li>Block or report anyone.</li>
        <li>Delete your account from Settings. This removes your profile and photos. Records related to safety reports may be kept as long as needed to protect other members or meet legal obligations.</li>
    </ul>
    <h2>Security</h2>
    <p>Passwords are hashed, connections are encrypted with HTTPS, and access to moderation tools is limited to authorised staff.</p>
    <h2>Contact</h2>
    <p>For privacy questions or requests, write to <a class="link" href="mailto:<?= e(setting('support_email')) ?>"><?= e(setting('support_email')) ?></a>.</p>
</article>
