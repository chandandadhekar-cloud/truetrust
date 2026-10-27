<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Settings;
use App\Models\Photo;
use App\Models\User;

final class OnboardingController extends Controller
{
    private const TITLES = [
        'basics'   => 'About you',
        'identity' => 'Who you are',
        'location' => 'Your city',
        'photos'   => 'Your photos',
        'about'    => 'Your vibe',
        'prompts'  => 'Prompts',
    ];

    public function show(string $step): string
    {
        $me = $this->guard($step);
        return view('onboarding/' . $step, [
            'title'    => self::TITLES[$step],
            'me'       => $me,
            'step'     => $step,
            'index'    => array_search($step, User::ONBOARDING_STEPS, true),
            'total'    => count(User::ONBOARDING_STEPS),
            'photos'   => $step === 'photos' ? Photo::forUser((int) $me['id']) : [],
            'scripts'  => ['onboarding'],
        ]);
    }

    public function save(string $step): void
    {
        $me = $this->guard($step);
        $id = (int) $me['id'];
        $errors = [];
        $data = [];

        switch ($step) {
            case 'basics':
                $name = preg_replace('/\s+/', ' ', Request::str('first_name'));
                if (!preg_match('/^[\p{L}][\p{L} .\'-]{0,29}$/u', $name)) {
                    $errors['first_name'] = 'Use your first name, letters only.';
                }
                $d = Request::int('dob_day');
                $m = Request::int('dob_month');
                $y = Request::int('dob_year');
                $dob = checkdate($m, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $m, $d) : null;
                if (!$dob) {
                    $errors['dob'] = 'Pick your full date of birth.';
                } elseif ($dob > User::minDob()) {
                    $errors['dob'] = 'You need to be at least ' . max(18, Settings::int('min_age')) . ' to use ' . setting('site_name') . '.';
                } elseif (age($dob) > 80) {
                    $errors['dob'] = 'Please check your year of birth.';
                }
                $data = ['first_name' => mb_convert_case($name, MB_CASE_TITLE), 'dob' => $dob];
                break;

            case 'identity':
                $gender = Request::str('gender');
                $into = Request::str('interested_in');
                if (!array_key_exists($gender, opt('gender'))) {
                    $errors['gender'] = 'Choose one.';
                }
                if (!array_key_exists($into, opt('interested_in'))) {
                    $errors['interested_in'] = 'Choose who you want to meet.';
                }
                $data = ['gender' => $gender, 'interested_in' => $into];
                break;

            case 'location':
                $city = find_city(Request::str('city'));
                if (!$city) {
                    $errors['city'] = 'Pick your city from the list.';
                    break;
                }
                // We store the city centre, never a member's exact position.
                $data = ['city' => $city[0], 'state' => $city[1], 'lat' => $city[2], 'lng' => $city[3]];
                break;

            case 'photos':
                if (Photo::countFor($id) === 0) {
                    $errors['photos'] = 'Add at least one photo of yourself.';
                }
                break;

            case 'about':
                $looking = Request::str('looking_for');
                $bio = trim(preg_replace("/\r\n?/", "\n", Request::str('bio')));
                $interests = array_values(array_intersect(Request::arr('interests'), opt('interests')));
                if (!array_key_exists($looking, opt('looking_for'))) {
                    $errors['looking_for'] = 'Choose what you are looking for.';
                }
                if (mb_strlen($bio) > 500) {
                    $errors['bio'] = 'Keep it under 500 characters.';
                }
                if (count($interests) < 3) {
                    $errors['interests'] = 'Pick at least 3 interests.';
                }
                $data = ['looking_for' => $looking, 'bio' => $bio, 'interests' => implode(',', array_slice($interests, 0, 8))];
                break;

            case 'prompts':
                $prompts = self::collectPrompts();
                $myAge = age($me['dob']);
                $data = [
                    'prompts'      => json_encode($prompts, JSON_UNESCAPED_UNICODE),
                    'onboarded_at' => now(),
                    'pref_age_min' => max(18, $myAge - 5),
                    'pref_age_max' => min(70, $myAge + 6),
                ];
                break;
        }

        if ($errors) {
            with_errors($errors);
            redirect('/onboarding/' . $step);
        }
        if ($data) {
            User::update($id, $data);
        }
        Auth::refresh();

        if ($step === 'prompts') {
            flash('success', 'Your profile is live. Happy swiping!');
            redirect('/discover');
        }
        redirect('/onboarding/' . User::nextOnboardingStep(Auth::user()));
    }

    /** @return list<array{q:string,a:string}> */
    public static function collectPrompts(): array
    {
        $out = [];
        $qs = Request::arr('prompt_q');
        $as = Request::arr('prompt_a');
        foreach ($qs as $i => $q) {
            $q = is_string($q) ? trim($q) : '';
            $a = trim((string) ($as[$i] ?? ''));
            if ($q !== '' && $a !== '' && in_array($q, opt('prompts'), true)) {
                $out[] = ['q' => $q, 'a' => mb_substr($a, 0, 160)];
            }
        }
        return array_slice($out, 0, 3);
    }

    /** Keeps members on the right step and out of onboarding once finished. */
    private function guard(string $step): array
    {
        $me = $this->me();
        if (!in_array($step, User::ONBOARDING_STEPS, true)) {
            abort(404);
        }
        if (Settings::bool('email_verification') && !$me['email_verified_at']) {
            redirect('/verify-email');
        }
        if ($me['onboarded_at']) {
            redirect('/discover');
        }
        $next = User::nextOnboardingStep($me);
        $steps = User::ONBOARDING_STEPS;
        if (array_search($step, $steps, true) > array_search($next, $steps, true)) {
            redirect('/onboarding/' . $next);
        }
        return $me;
    }
}
