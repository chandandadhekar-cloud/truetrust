<?php defined('APP_ROOT') || exit;
$list = cities();
usort($list, static fn ($a, $b) => strcmp($a[0], $b[0]));
?>
<div class="ph">
    <div><h1>Seed profiles</h1><p>Generate realistic Indian profiles to fill Discover while the community grows.</p></div>
    <div class="actions"><a href="/admin/users?type=seed" class="btn btn-outline">Browse all seed profiles</a></div>
</div>

<div class="kpis">
    <div class="kpi"><span>Total</span><b><?= $stats['total'] ?? 0 ?></b></div>
    <div class="kpi"><span>Women</span><b><?= $stats['women'] ?? 0 ?></b></div>
    <div class="kpi"><span>Men</span><b><?= $stats['men'] ?? 0 ?></b></div>
    <div class="kpi<?= !empty($stats['no_photo']) ? ' alert' : '' ?>"><span>Waiting for photos</span><b><?= $stats['no_photo'] ?? 0 ?></b><small style="color:var(--muted)"><?= $stats['no_photo_women'] ?? 0 ?> women · <?= $stats['no_photo_men'] ?? 0 ?> men</small></div>
    <div class="kpi"><span>In Discover</span><div style="margin-top:14px"><?= $visible ? '<span class="tag tag-ok">Shown to members</span>' : '<a class="tag" href="/admin/settings#discover">Hidden by setting</a>' ?></div></div>
</div>
<?php if (!empty($stats['no_photo'])): ?>
<div class="notice" style="margin-bottom:18px"><?= icon('info', 18) ?><span>Profiles without photos are not shown in Discover. Use <b>Bulk add photos</b> below, or upload per profile from its page.</span></div>
<?php endif; ?>

<div class="grid-2">
    <form class="panel" method="post" action="/admin/seed/generate" data-submit-once>
        <?= csrf_field() ?>
        <div class="panel-h"><h2>Generate profiles</h2></div>
        <div class="panel-b">
            <div class="form-grid">
                <label class="field"><span>How many</span><input class="input" type="number" name="count" value="20" min="1" max="500"></label>
                <label class="field"><span>Gender</span><select class="select" name="gender"><option value="mixed">Mixed (50/50)</option><option value="woman">Women</option><option value="man">Men</option></select></label>
                <label class="field"><span>Age from</span><input class="input" type="number" name="age_min" value="21" min="18" max="60"></label>
                <label class="field"><span>Age to</span><input class="input" type="number" name="age_max" value="33" min="18" max="60"></label>
                <label class="field full"><span>Cities (leave empty for all of India, weighted to metros)</span>
                    <select class="select" name="cities[]" multiple size="7" style="background-image:none;padding-right:13px"><?php foreach ($list as $c): ?><option value="<?= e($c[0]) ?>"><?= e($c[0]) ?>, <?= e($c[1]) ?></option><?php endforeach; ?></select>
                    <p class="hint">Hold Ctrl / Cmd to pick several.</p>
                </label>
                <label class="field"><span>% with verified badge</span><input class="input" type="number" name="verified_pct" value="40" min="0" max="100"></label>
            </div>
            <p class="hint" style="margin-bottom:14px">Names, surnames, religion, mother tongue and languages follow each city's communities. Jobs, education, bios, prompts, interests, height and lifestyle are varied so profiles don't look templated.</p>
            <button class="btn btn-dark"><?= icon('sparkle', 18) ?> Generate</button>
        </div>
    </form>

    <form class="panel" method="post" action="/admin/seed/photos" enctype="multipart/form-data" data-submit-once>
        <?= csrf_field() ?>
        <div class="panel-h"><h2>Bulk add photos</h2></div>
        <div class="panel-b">
            <p class="muted small" style="margin-bottom:14px">Upload a batch of photos. They're given, in order, to seed profiles of the chosen gender that don't have photos yet.</p>
            <div class="form-grid">
                <label class="field"><span>Photos are of</span><select class="select" name="gender"><option value="woman">Women</option><option value="man">Men</option></select></label>
                <label class="field"><span>Photos per profile</span><select class="select" name="per_profile"><?php for ($i = 1; $i <= 6; $i++): ?><option value="<?= $i ?>"<?= $i === 1 ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></label>
                <label class="field full"><span>Photos</span><input class="input" type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple required style="padding:9px"></label>
            </div>
            <p class="hint" style="margin-bottom:14px">Your server limits uploads to <?= e(ini_get('post_max_size')) ?> per batch and <?= (int) ini_get('max_file_uploads') ?> files. Send large sets in several batches.</p>
            <button class="btn btn-dark"><?= icon('camera', 18) ?> Upload & assign</button>
        </div>
    </form>
</div>

<div class="panel" id="ai" style="margin-top:18px" data-ai-runner data-pending="<?= (int) ($ai['counts']['queued'] + $ai['counts']['running']) ?>">
    <div class="panel-h">
        <h2>AI photos</h2>
        <span class="muted small">Spent $<span data-ai-spent><?= number_format($ai['spent'], 2) ?></span> of $<?= number_format($ai['budget'], 2) ?> cap · <a class="link" href="/admin/settings#ai">settings</a></span>
    </div>
    <?php if (!$ai['configured']): ?>
        <div class="panel-b"><div class="notice"><?= icon('info', 18) ?><span>Add your OpenAI API key in <a class="link" href="/admin/settings#ai">Settings → AI photos</a> to generate photos for seed profiles.</span></div></div>
    <?php else: ?>
    <div class="panel-b">
        <div class="grid-2" style="align-items:start">
            <form method="post" action="/admin/seed/ai/queue" data-submit-once>
                <?= csrf_field() ?>
                <p class="muted small" style="margin-bottom:14px">Creates photos for seed profiles that have none. The first photo defines the person; the rest reuse that face. Members see an "AI-generated photos" label on these profiles.</p>
                <div class="form-grid">
                    <label class="field"><span>Profiles</span><input class="input" type="number" name="profiles" value="5" min="1" max="200"></label>
                    <label class="field"><span>Gender</span><select class="select" name="gender"><option value="">Any</option><option value="woman">Women</option><option value="man">Men</option></select></label>
                    <label class="field"><span>Photos per profile</span><select class="select" name="shots"><?php for ($i = 1; $i <= 4; $i++): ?><option<?= $i === 3 ? ' selected' : '' ?>><?= $i ?></option><?php endfor; ?></select></label>
                    <label class="field"><span>Quality</span><select class="select" name="quality"><?php foreach ($ai['estimates'] as $q => $cost): ?><option value="<?= $q ?>"<?= selected($ai['quality'], $q) ?>><?= ucfirst($q) ?> · ~$<?= number_format($cost, 3) ?>/photo</option><?php endforeach; ?></select></label>
                </div>
                <button class="btn btn-outline"><?= icon('plus', 18) ?> Add to queue</button>
            </form>
            <div>
                <div class="ai-counts">
                    <div><b data-ai-count="queued"><?= $ai['counts']['queued'] + $ai['counts']['running'] ?></b><span>Waiting</span></div>
                    <div><b data-ai-count="done"><?= $ai['counts']['done'] ?></b><span>Created</span></div>
                    <div><b data-ai-count="failed"><?= $ai['counts']['failed'] ?></b><span>Failed</span></div>
                </div>
                <div class="ai-bar"><i data-ai-bar></i></div>
                <p class="small muted" data-ai-msg style="min-height:20px;margin:8px 0 12px">Each photo takes about 20–60 seconds. Keep this tab open while it runs.</p>
                <div class="row" style="flex-wrap:wrap">
                    <button type="button" class="btn btn-dark" data-ai-start><?= icon('zap', 18) ?> Start</button>
                    <button type="button" class="btn btn-outline hidden" data-ai-stop>Pause</button>
                    <?php if ($ai['counts']['failed']): ?><form method="post" action="/admin/seed/ai/manage"><?= csrf_field() ?><input type="hidden" name="do" value="retry"><button class="btn btn-ghost btn-xs">Retry failed</button></form><?php endif; ?>
                    <?php if ($ai['counts']['queued'] || $ai['counts']['failed']): ?><form method="post" action="/admin/seed/ai/manage" data-confirm="Remove all waiting and failed photos from the queue?"><?= csrf_field() ?><input type="hidden" name="do" value="clear"><button class="btn btn-ghost btn-xs">Clear queue</button></form><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="ai-strip" data-ai-strip>
            <?php foreach ($ai['recent'] as $r): ?><a href="/admin/users/<?= (int) $r['user_id'] ?>" title="<?= e($r['first_name']) ?>"><img src="<?= photo_url((int) $r['photo_id'], 'thumb') ?>" alt="" loading="lazy"></a><?php endforeach; ?>
        </div>
        <?php if ($ai['failed']): ?>
            <div style="margin-top:14px">
                <?php foreach ($ai['failed'] as $f): ?><p class="small" style="padding:4px 0"><span class="tag tag-bad">Failed</span> <a class="link" href="/admin/users/<?= (int) $f['user_id'] ?>"><?= e($f['first_name']) ?></a> photo <?= (int) $f['shot'] + 1 ?>: <?= e($f['error']) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<div class="grid-side" style="margin-top:18px">
    <div class="panel">
        <div class="panel-h"><h2>Latest seed profiles</h2></div>
        <div class="tbl-wrap"><table class="tbl"><tbody>
            <?php foreach ($recent as $u): ?>
                <tr>
                    <td><?= partial('admin/partials/who', ['u' => $u, 'photoId' => $photos[(int) $u['id']][0] ?? null]) ?></td>
                    <td><?= age($u['dob']) ?> <span class="muted"><?= $u['gender'] === 'woman' ? 'F' : 'M' ?></span></td>
                    <td><?= e($u['city']) ?></td>
                    <td class="muted"><?= e($u['job_title']) ?></td>
                    <td class="num"><?= empty($photos[(int) $u['id']]) ? '<a class="btn btn-outline btn-xs" href="/admin/users/' . (int) $u['id'] . '#photos">Add photos</a>' : '' ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?><tr><td class="muted" style="padding:24px">No seed profiles yet. Generate some above.</td></tr><?php endif; ?>
        </tbody></table></div>
    </div>
    <div>
        <div class="panel">
            <div class="panel-h"><h2>By city</h2></div>
            <div class="panel-b">
                <?php foreach ($byCity as $c): ?><div class="row" style="padding:4px 0"><span><?= e($c['city']) ?></span><span class="spacer"></span><b><?= (int) $c['n'] ?></b></div><?php endforeach; ?>
                <?php if (!$byCity): ?><p class="muted">—</p><?php endif; ?>
            </div>
        </div>
        <div class="panel">
            <div class="panel-h"><h2>Manage all</h2></div>
            <div class="panel-b">
                <p class="small muted" style="margin-bottom:12px">As real members join, hide seed profiles in one click, or turn them off globally in Settings.</p>
                <div class="action-grid">
                    <form method="post" action="/admin/seed/purge"><?= csrf_field() ?><input type="hidden" name="mode" value="hide"><button class="btn btn-outline btn-xs">Hide all</button></form>
                    <form method="post" action="/admin/seed/purge"><?= csrf_field() ?><input type="hidden" name="mode" value="unhide"><button class="btn btn-outline btn-xs">Show all</button></form>
                </div>
                <?php if (App\Core\AdminAuth::isOwner()): ?>
                <form method="post" action="/admin/seed/purge" style="margin-top:14px" data-confirm="Permanently delete ALL seed profiles, their photos and any likes or chats with them?">
                    <?= csrf_field() ?><input type="hidden" name="mode" value="delete">
                    <label class="field"><span>Type DELETE to remove every seed profile</span><input class="input" name="confirm" autocomplete="off"></label>
                    <button class="btn btn-danger btn-xs">Delete all seed profiles</button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="<?= asset('js/admin-ai.js') ?>" defer></script>
