<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\DB;
use App\Core\Request;
use App\Models\Block;
use App\Models\MatchModel;
use App\Models\Photo;
use App\Models\User;

final class ProfileController extends Controller
{
    /** Another member's full profile. */
    public function show(int $id): string
    {
        $me = $this->me();
        $self = $id === (int) $me['id'];
        $u = $self ? $me : User::find($id);
        if (!$u || (!$self && ($u['status'] !== 'active' || !$u['onboarded_at'] || $u['is_hidden'] && !MatchModel::between((int) $me['id'], $id) || Block::between((int) $me['id'], $id)))) {
            abort(404, 'This profile is no longer available.');
        }
        $match = MatchModel::between((int) $me['id'], $id);
        $mySwipe = DB::value('SELECT action FROM swipes WHERE user_id = ? AND target_id = ?', [$me['id'], $id]);
        $theyLiked = DB::value("SELECT action FROM swipes WHERE user_id = ? AND target_id = ? AND action IN ('like','superlike')", [$id, $me['id']]);

        return view('profile/show', [
            'title'     => $u['first_name'],
            'nav'       => true,
            'card'      => $self ? User::card($u, null, Photo::idsFor($id, false)) : User::card($u, $me),
            'self'      => $self,
            'match'     => $match && !$match['unmatched_at'] ? $match : null,
            'mySwipe'   => $mySwipe,
            'theyLiked' => $theyLiked,
            'scripts'   => ['profile'],
        ]);
    }

    /** The member's own profile hub. */
    public function mine(): string
    {
        $me = $this->me();
        $id = (int) $me['id'];
        $stats = DB::one(
            "SELECT
                (SELECT COUNT(*) FROM swipes WHERE target_id = ? AND action IN ('like','superlike')) AS likes,
                (SELECT COUNT(*) FROM matches WHERE (user_a = ? OR user_b = ?) AND unmatched_at IS NULL) AS matches,
                (SELECT COUNT(*) FROM swipes WHERE target_id = ?) AS views",
            [$id, $id, $id, $id]
        );
        return view('profile/me', [
            'title'    => 'Profile',
            'nav'      => true,
            'me'       => $me,
            'photo'    => Photo::primaryId($id, false),
            'complete' => User::completeness($me),
            'stats'    => $stats,
        ]);
    }

    public function edit(): string
    {
        $me = $this->me();
        return view('profile/edit', [
            'title'   => 'Edit profile',
            'nav'     => true,
            'me'      => $me,
            'photos'  => Photo::forUser((int) $me['id']),
            'scripts' => ['onboarding'],
        ]);
    }

    public function update(): void
    {
        $me = $this->me();
        $errors = [];
        $bio = trim(preg_replace("/\r\n?/", "\n", Request::str('bio')));
        if (mb_strlen($bio) > 500) {
            $errors['bio'] = 'Keep it under 500 characters.';
        }
        $height = Request::int('height_cm');
        if ($height && ($height < 120 || $height > 230)) {
            $errors['height_cm'] = 'Enter a height between 120 and 230 cm.';
        }
        $interests = array_values(array_intersect(Request::arr('interests'), opt('interests')));
        if (count($interests) > 8) {
            $errors['interests'] = 'Choose up to 8 interests.';
        }
        $city = find_city(Request::str('city'));
        if (!$city) {
            $errors['city'] = 'Pick a city from the list.';
        }
        if ($errors) {
            with_errors($errors);
            redirect('/profile/edit');
        }

        $pick = static fn (string $key, array $allowed) => in_array(Request::str($key), $allowed, true) ? Request::str($key) : '';
        $langs = array_values(array_intersect(Request::arr('languages'), opt('languages')));

        User::update((int) $me['id'], [
            'bio'           => $bio,
            'job_title'     => mb_substr(Request::str('job_title'), 0, 100),
            'company'       => mb_substr(Request::str('company'), 0, 100),
            'education'     => mb_substr(Request::str('education'), 0, 120),
            'height_cm'     => $height ?: null,
            'religion'      => $pick('religion', opt('religion')),
            'languages'     => implode(',', array_slice($langs, 0, 6)),
            'mother_tongue' => $pick('mother_tongue', opt('languages')),
            'drinking'      => $pick('drinking', array_keys(opt('drinking'))),
            'smoking'       => $pick('smoking', array_keys(opt('smoking'))),
            'diet'          => $pick('diet', array_keys(opt('diet'))),
            'looking_for'   => $pick('looking_for', array_keys(opt('looking_for'))) ?: $me['looking_for'],
            'interests'     => implode(',', $interests),
            'prompts'       => json_encode(OnboardingController::collectPrompts(), JSON_UNESCAPED_UNICODE),
            'city'          => $city[0],
            'state'         => $city[1],
            'lat'           => $city[2],
            'lng'           => $city[3],
        ]);
        Auth::refresh();
        flash('success', 'Profile saved.');
        redirect('/profile');
    }
}
