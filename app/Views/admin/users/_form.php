<?php defined('APP_ROOT') || exit;
/* Admin profile editor. $u may be an empty array for new profiles. */
$v = static fn ($k, $d = '') => old($k, $u[$k] ?? $d);
$interests = isset($u['interests']) ? App\Models\User::interests($u) : [];
$langs = isset($u['languages']) ? App\Models\User::languages($u) : [];
$prompts = isset($u['prompts']) ? App\Models\User::prompts($u) : [];
$list = cities();
usort($list, static fn ($a, $b) => strcmp($a[0], $b[0]));
$sel = static function (string $name, array $opts, $current, string $blank = '—') {
    $h = '<select class="select" name="' . $name . '"><option value="">' . e($blank) . '</option>';
    foreach ($opts as $k => $label) {
        $key = is_int($k) ? $label : $k;
        $h .= '<option value="' . e($key) . '"' . selected((string) $current, $key) . '>' . e($label) . '</option>';
    }
    return $h . '</select>';
};
?>
<div class="form-grid">
    <label class="field"><span>First name *</span><input class="input" name="first_name" value="<?= e($v('first_name')) ?>" required></label>
    <label class="field"><span>Last name (never shown to members)</span><input class="input" name="last_name" value="<?= e($v('last_name')) ?>"></label>
    <label class="field"><span>Email</span><input class="input" type="email" name="email" value="<?= e($v('email')) ?>" placeholder="<?= $u ? '' : 'Leave empty for seed profiles' ?>"></label>
    <label class="field"><span>Date of birth</span><input class="input" type="date" name="dob" value="<?= e($v('dob')) ?>" max="<?= e(App\Models\User::minDob()) ?>"></label>
    <label class="field"><span>Gender</span><?= $sel('gender', opt('gender'), $v('gender')) ?></label>
    <label class="field"><span>Interested in</span><?= $sel('interested_in', opt('interested_in'), $v('interested_in')) ?></label>
    <label class="field"><span>City</span>
        <select class="select" name="city"><option value="">—</option><?php foreach ($list as $c): ?><option value="<?= e($c[0]) ?>"<?= selected($v('city'), $c[0]) ?>><?= e($c[0]) ?>, <?= e($c[1]) ?></option><?php endforeach; ?></select>
    </label>
    <label class="field"><span>Looking for</span><?= $sel('looking_for', opt('looking_for'), $v('looking_for')) ?></label>
    <label class="field full"><span>Bio</span><textarea class="textarea" name="bio" maxlength="1000" style="min-height:90px"><?= e($v('bio')) ?></textarea></label>
    <label class="field"><span>Job title</span><input class="input" name="job_title" value="<?= e($v('job_title')) ?>"></label>
    <label class="field"><span>Company</span><input class="input" name="company" value="<?= e($v('company')) ?>"></label>
    <label class="field"><span>Education</span><input class="input" name="education" value="<?= e($v('education')) ?>"></label>
    <label class="field"><span>Height (cm)</span><input class="input" type="number" name="height_cm" min="120" max="230" value="<?= e($v('height_cm')) ?>"></label>
    <label class="field"><span>Religion</span><?= $sel('religion', opt('religion'), $v('religion')) ?></label>
    <label class="field"><span>Mother tongue</span><?= $sel('mother_tongue', opt('languages'), $v('mother_tongue')) ?></label>
    <label class="field"><span>Drinking</span><?= $sel('drinking', opt('drinking'), $v('drinking')) ?></label>
    <label class="field"><span>Smoking</span><?= $sel('smoking', opt('smoking'), $v('smoking')) ?></label>
    <label class="field"><span>Diet</span><?= $sel('diet', opt('diet'), $v('diet')) ?></label>
    <div class="field full"><span class="label">Languages</span><div class="chips">
        <?php foreach (opt('languages') as $l): ?><label class="chip"><input type="checkbox" name="languages[]" value="<?= e($l) ?>"<?= checked(in_array($l, $langs, true)) ?>><?= e($l) ?></label><?php endforeach; ?>
    </div></div>
    <div class="field full"><span class="label">Interests</span><div class="chips">
        <?php foreach (opt('interests') as $i): ?><label class="chip"><input type="checkbox" name="interests[]" value="<?= e($i) ?>"<?= checked(in_array($i, $interests, true)) ?>><?= e($i) ?></label><?php endforeach; ?>
    </div></div>
    <?php for ($i = 0; $i < 3; $i++): $pr = $prompts[$i] ?? ['q' => '', 'a' => '']; ?>
        <label class="field"><span>Prompt <?= $i + 1 ?></span><?= $sel('prompt_q[]', array_combine(opt('prompts'), opt('prompts')), $pr['q']) ?></label>
        <label class="field"><span>Answer</span><input class="input" name="prompt_a[]" value="<?= e($pr['a']) ?>" maxlength="200"></label>
    <?php endfor; ?>
</div>
