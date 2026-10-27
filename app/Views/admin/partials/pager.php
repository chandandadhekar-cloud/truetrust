<?php defined('APP_ROOT') || exit;
if ($p['pages'] <= 1) { return; }
$q = $_GET;
$link = static function (int $page) use ($q) { $q['page'] = $page; return '?' . http_build_query($q); };
?>
<div class="pager">
    <span><?= number_format($p['total']) ?> total · page <?= $p['page'] ?> of <?= $p['pages'] ?></span>
    <?php if ($p['page'] > 1): ?><a href="<?= e($link($p['page'] - 1)) ?>">Previous</a><?php endif; ?>
    <?php if ($p['page'] < $p['pages']): ?><a href="<?= e($link($p['page'] + 1)) ?>">Next</a><?php endif; ?>
</div>
