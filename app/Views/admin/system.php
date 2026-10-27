<?php defined('APP_ROOT') || exit; ?>
<div class="ph"><div><h1>Health</h1><p>Server capabilities the app depends on.</p></div></div>
<div class="grid-side">
    <div class="panel"><div class="tbl-wrap"><table class="tbl">
        <thead><tr><th>Check</th><th>Value</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($checks as [$label, $value, $ok]): ?>
            <tr><td><b><?= e($label) ?></b></td><td><?= e($value) ?></td><td class="num"><?= $ok ? '<span class="tag tag-ok">OK</span>' : '<span class="tag tag-warn">Check</span>' ?></td></tr>
        <?php endforeach; ?>
        </tbody>
    </table></div></div>
    <div class="panel">
        <div class="panel-h"><h2>Database migrations</h2></div>
        <div class="panel-b">
            <?php foreach ($migrations as $m): ?><div style="padding:6px 0;border-bottom:1px solid var(--line)"><div class="mono"><?= e($m['name']) ?></div><small class="muted">Ran <?= date('j M Y, g:i A', strtotime($m['ran_at'])) ?></small></div><?php endforeach; ?>
        </div>
    </div>
</div>
