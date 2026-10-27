<?php defined('APP_ROOT') || exit; ?>
<header class="appbar"><div class="appbar-inner">
    <a href="<?= e($back ?? '/') ?>" class="icon-btn" aria-label="Back"><?= icon('back', 24) ?></a>
    <span class="spacer"></span>
    <span class="brand"><?= partial('partials/logo') ?></span>
    <span class="spacer"></span>
    <span style="width:42px"></span>
</div></header>
