<?php defined('APP_ROOT') || exit; ?>
<?= partial('pages/_bar', ['title' => $title]) ?>
<article class="prose">
    <p class="updated">Last updated 27 October 2026</p>
    <p>These terms explain the rules for using <?= e(setting('site_name')) ?>. By creating an account you agree to them.</p>
    <h2>Who can use <?= e(setting('site_name')) ?></h2>
    <p>You must be at least 18 years old and legally able to enter into this agreement. One account per person. You may not use <?= e(setting('site_name')) ?> if you have been removed from it before.</p>
    <h2>It's free</h2>
    <p><?= e(setting('site_name')) ?> does not charge for any feature. If anyone asks you to pay to use <?= e(setting('site_name')) ?>, unlock a profile or "verify" a match, it is a scam. Report it.</p>
    <h2>Your account</h2>
    <ul>
        <li>Keep your login details private. You are responsible for activity on your account.</li>
        <li>Use your real age and your own photos. Impersonating someone is not allowed.</li>
        <li>You can pause or delete your account at any time from Settings.</li>
    </ul>
    <h2>Your content</h2>
    <p>You own the photos and text you post. You give <?= e(setting('site_name')) ?> permission to display them to other members in order to run the service. Don't post anything you don't have the right to share.</p>
    <h2>Behaviour</h2>
    <p>Follow our <a class="link" href="/guidelines">Community Guidelines</a>. We may remove content, restrict features, suspend or ban accounts that break them, with or without notice.</p>
    <h2>Moderation</h2>
    <p>To keep members safe, our team may review profiles, photos, reports and messages, and automated checks flag messages that look like scams or harassment. See the <a class="link" href="/privacy">Privacy Policy</a> for details.</p>
    <h2>Sample profiles</h2>
    <p>To help new areas get started, some profiles may be created and managed by the <?= e(setting('site_name')) ?> team. They never ask for money, gifts or personal details.</p>
    <h2>Meeting people</h2>
    <p><?= e(setting('site_name')) ?> does not run criminal background checks and cannot guarantee anyone's identity or intentions. Please read our <a class="link" href="/safety">safety tips</a> and use good judgement, especially when meeting in person.</p>
    <h2>Liability</h2>
    <p><?= e(setting('site_name')) ?> is provided "as is". To the extent the law allows, we are not liable for the conduct of members on or off the service, or for indirect losses arising from using it.</p>
    <h2>Changes</h2>
    <p>We may update these terms. If the changes are significant we will let you know in the app. Continuing to use <?= e(setting('site_name')) ?> means you accept the updated terms.</p>
    <h2>Contact</h2>
    <p>Questions? Write to <a class="link" href="mailto:<?= e(setting('support_email')) ?>"><?= e(setting('support_email')) ?></a>. These terms are governed by the laws of India.</p>
</article>
