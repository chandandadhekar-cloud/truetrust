<?php defined('APP_ROOT') || exit;
$steps = App\Models\User::ONBOARDING_STEPS;
$prev = $index > 0 ? '/onboarding/' . $steps[$index - 1] : null;
?>
<header class="appbar"><div class="appbar-inner">
    <?php if ($prev): ?>
        <a href="<?= $prev ?>" class="icon-btn" aria-label="Back"><?= icon('back', 24) ?></a>
    <?php else: ?>
        <button class="icon-btn" type="submit" form="logout-form" aria-label="Sign out"><?= icon('x', 22) ?></button>
    <?php endif; ?>
    <div class="ob-progress"><i style="width:<?= round(($index + 1) / $total * 100) ?>%"></i></div>
    <span style="width:42px;text-align:right" class="small muted"><?= $index + 1 ?>/<?= $total ?></span>
</div></header>
