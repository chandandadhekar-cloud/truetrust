<?php defined('APP_ROOT') || exit; ?>
<?= partial('pages/_bar', ['title' => $title]) ?>
<article class="prose">
    <p><?= e(setting('site_name')) ?> works because people treat each other well. Breaking these rules can get your account suspended or banned.</p>
    <h2>Be yourself</h2>
    <p>Use recent photos of yourself and your real age. No fake profiles, no catfishing, no pretending to be someone else.</p>
    <h2>Be respectful</h2>
    <p>No harassment, threats, hate speech or bullying. Nobody owes you a reply. If someone isn't interested, move on.</p>
    <h2>Keep it appropriate</h2>
    <p>No nudity or sexual content in photos. Don't send explicit messages unless the other person clearly wants them, and never send them unsolicited.</p>
    <h2>No scams or selling</h2>
    <p>No requests for money, no promotions, no recruiting for businesses or "schemes", no links to other services for profit.</p>
    <h2>Adults only</h2>
    <p>You must be 18 or older. We remove anyone who appears to be under 18 and report child exploitation to the authorities.</p>
    <h2>Nothing illegal</h2>
    <p>Don't use <?= e(setting('site_name')) ?> for anything illegal, including selling drugs or weapons.</p>
    <h2>Report problems</h2>
    <p>If you see something that breaks these rules, report it. We review every report. When several different members report the same profile, it is hidden from Discover until our team has reviewed it.</p>
</article>
