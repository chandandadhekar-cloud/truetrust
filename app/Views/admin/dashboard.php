<?php defined('APP_ROOT') || exit;
$maxS = max(1, max($signups));
$maxM = max(1, max($messages));
?>
<div class="ph">
    <div><h1>Dashboard</h1><p><?= date('l, j F Y') ?></p></div>
    <div class="actions">
        <a href="/admin/seed" class="btn btn-outline"><?= icon('sparkle', 18) ?> Seed profiles</a>
        <a href="/admin/users/new" class="btn btn-dark"><?= icon('plus', 18) ?> New profile</a>
    </div>
</div>

<?php if ($counts['reports'] || $counts['flagged'] || $counts['photos']): ?>
<div class="notice" style="margin-bottom:18px"><?= icon('alert', 20) ?><span>
    Needs attention:
    <?php if ($counts['reports']): ?><a class="link" href="/admin/reports"><?= plural($counts['reports'], 'open report') ?></a> · <?php endif; ?>
    <?php if ($counts['flagged']): ?><a class="link" href="/admin/messages/flagged"><?= plural($counts['flagged'], 'flagged message') ?></a> · <?php endif; ?>
    <?php if ($counts['photos']): ?><a class="link" href="/admin/photos"><?= plural($counts['photos'], 'photo') ?> to review</a><?php endif; ?>
</span></div>
<?php endif; ?>

<div class="kpis">
    <div class="kpi"><span><?= icon('users', 15) ?> Real members</span><b><?= number_format($s['members']) ?></b><small>+<?= $s['new_week'] ?> this week</small></div>
    <div class="kpi"><span><?= icon('zap', 15) ?> Online now</span><b><?= number_format($s['online']) ?></b><small style="color:var(--muted)"><?= number_format($s['active_day']) ?> active today</small></div>
    <div class="kpi"><span><?= icon('user', 15) ?> New today</span><b><?= number_format($s['new_today']) ?></b><small style="color:var(--muted)"><?= $s['incomplete'] ?> haven't finished signup</small></div>
    <div class="kpi"><span><?= icon('heart', 15) ?> Matches</span><b><?= number_format($s['matches']) ?></b><small>+<?= $s['matches_today'] ?> today</small></div>
    <div class="kpi"><span><?= icon('chat', 15) ?> Messages</span><b><?= number_format($s['messages']) ?></b><small>+<?= $s['messages_today'] ?> today</small></div>
    <div class="kpi"><span><?= icon('refresh', 15) ?> Swipes today</span><b><?= number_format($s['swipes_today']) ?></b></div>
    <div class="kpi"><span><?= icon('sparkle', 15) ?> Seed profiles</span><b><?= number_format($s['seeds']) ?></b></div>
    <div class="kpi<?= $s['banned'] ? ' alert' : '' ?>"><span><?= icon('block', 15) ?> Banned</span><b><?= number_format($s['banned']) ?></b></div>
</div>

<div class="grid-2">
    <div class="panel">
        <div class="panel-h"><h2>Sign-ups, last 14 days</h2></div>
        <div class="panel-b"><div class="bars">
            <?php foreach ($signups as $d => $n): ?><div class="bar" title="<?= date('j M', strtotime($d)) ?>: <?= $n ?>"><i style="height:<?= round($n / $maxS * 100) ?>%"></i><span><?= date('j', strtotime($d)) ?></span></div><?php endforeach; ?>
        </div></div>
    </div>
    <div class="panel">
        <div class="panel-h"><h2>Messages, last 14 days</h2></div>
        <div class="panel-b"><div class="bars">
            <?php foreach ($messages as $d => $n): ?><div class="bar" title="<?= date('j M', strtotime($d)) ?>: <?= $n ?>"><i style="height:<?= round($n / $maxM * 100) ?>%;background:#2F2A35"></i><span><?= date('j', strtotime($d)) ?></span></div><?php endforeach; ?>
        </div></div>
    </div>
</div>

<div class="grid-side" style="margin-top:18px">
    <div class="panel">
        <div class="panel-h"><h2>Newest members</h2><div class="actions"><a href="/admin/users?type=real" class="btn btn-ghost btn-xs">View all</a></div></div>
        <div class="tbl-wrap"><table class="tbl">
            <tbody>
            <?php foreach ($recent as $u): ?>
                <tr>
                    <td><?= partial('admin/partials/who', ['u' => $u, 'photoId' => App\Models\Photo::primaryId((int) $u['id'], false)]) ?></td>
                    <td><?= $u['dob'] ? age($u['dob']) . ' · ' : '' ?><?= e(opt_label('gender', $u['gender'])) ?></td>
                    <td><?= e($u['city']) ?></td>
                    <td><?= $u['onboarded_at'] ? '<span class="tag tag-ok">Complete</span>' : '<span class="tag tag-warn">Onboarding</span>' ?></td>
                    <td class="num muted"><?= time_ago($u['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?><tr><td class="muted">No members yet.</td></tr><?php endif; ?>
            </tbody>
        </table></div>
    </div>
    <div>
        <div class="panel">
            <div class="panel-h"><h2>Members by gender</h2></div>
            <div class="panel-b">
                <?php $tot = max(1, $s['men'] + $s['women']); ?>
                <div style="display:flex;height:12px;border-radius:6px;overflow:hidden;background:var(--surface-2)"><i style="width:<?= $s['women'] / $tot * 100 ?>%;background:var(--brand)"></i><i style="width:<?= $s['men'] / $tot * 100 ?>%;background:#2F2A35"></i></div>
                <div class="row small" style="margin-top:10px"><span><b style="color:var(--brand)">●</b> Women <?= $s['women'] ?></span><span class="spacer"></span><span><b>●</b> Men <?= $s['men'] ?></span></div>
            </div>
        </div>
        <div class="panel">
            <div class="panel-h"><h2>Top cities</h2></div>
            <div class="panel-b">
                <?php foreach ($topCities as $c): ?><div class="row" style="padding:5px 0"><span><?= e($c['city']) ?></span><span class="spacer"></span><b><?= (int) $c['n'] ?></b></div><?php endforeach; ?>
                <?php if (!$topCities): ?><p class="muted">No data yet.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
