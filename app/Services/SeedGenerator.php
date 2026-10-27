<?php
namespace App\Services;

defined('APP_ROOT') || exit;

use App\Core\DB;

/**
 * Generates admin "seed" profiles with internally consistent Indian details:
 * the city decides the community mix, which decides name, surname, religion
 * and mother tongue. Seed accounts have no usable password and are flagged
 * with is_seed so they can be filtered, hidden or purged at any time.
 */
final class SeedGenerator
{
    private const METROS = ['Mumbai', 'New Delhi', 'Bengaluru', 'Hyderabad', 'Chennai', 'Kolkata', 'Pune', 'Ahmedabad', 'Gurugram', 'Noida'];
    private const TIER2 = ['Jaipur', 'Lucknow', 'Chandigarh', 'Indore', 'Kochi', 'Coimbatore', 'Nagpur', 'Surat', 'Vadodara', 'Bhubaneswar', 'Thane', 'Navi Mumbai', 'Visakhapatnam', 'Guwahati', 'Bhopal', 'Mysuru', 'Thiruvananthapuram', 'Dehradun', 'Ludhiana', 'Panaji'];

    private array $data;

    public function __construct()
    {
        $this->data = require APP_ROOT . '/app/Data/seed.php';
    }

    /**
     * @param array{gender?:string, age_min?:int, age_max?:int, cities?:array, verified_pct?:int} $opts
     * @return int[] created user ids
     */
    public function generate(int $count, array $opts = []): array
    {
        $count = max(1, min(500, $count));
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = DB::insert('users', $this->profile($opts));
        }
        return $ids;
    }

    public function profile(array $opts = []): array
    {
        $genderOpt = $opts['gender'] ?? 'mixed';
        $gender = $genderOpt === 'mixed' ? (mt_rand(1, 100) <= 50 ? 'woman' : 'man') : $genderOpt;
        $ageMin = max(18, (int) ($opts['age_min'] ?? 21));
        $ageMax = max($ageMin, min(60, (int) ($opts['age_max'] ?? 34)));

        $city = $this->pickCity($opts['cities'] ?? []);
        [$cityName, $state, $lat, $lng] = $city;
        $groupKey = $this->weighted($this->data['regions'][$state] ?? ['hindi' => 1]);
        $group = $this->data['groups'][$groupKey];

        $first = $this->pick($group[$gender]);
        $last = $this->pick($group['surnames']);
        if ($groupKey === 'punjabi') {
            if ($gender === 'woman' && mt_rand(1, 100) <= 35) {
                $last = 'Kaur';
            } elseif ($gender === 'man' && mt_rand(1, 100) <= 30) {
                $last = 'Singh';
            }
        }

        $age = $this->age($ageMin, $ageMax);
        $dob = date('Y-m-d', strtotime(sprintf('-%d years -%d days', $age, mt_rand(0, 364))));

        $religion = $group['religion'];
        if ($religion === 'Hindu') {
            $r = mt_rand(1, 100);
            $religion = $r <= 7 ? 'Spiritual' : ($r <= 10 ? 'Agnostic' : 'Hindu');
        }
        $tongue = $group['tongue'];
        if ($groupKey === 'muslim' && $state === 'Kerala') {
            $tongue = 'Malayalam';
        } elseif ($groupKey === 'muslim' && in_array($state, ['Tamil Nadu', 'Puducherry'], true)) {
            $tongue = 'Tamil';
        } elseif ($groupKey === 'jain' && $state === 'Rajasthan') {
            $tongue = 'Hindi';
        } elseif ($groupKey === 'jain' && $state === 'Maharashtra') {
            $tongue = mt_rand(0, 1) ? 'Marathi' : 'Gujarati';
        }
        $langs = [$tongue];
        if (mt_rand(1, 100) <= 88) {
            $langs[] = 'English';
        }
        if ($tongue !== 'Hindi' && mt_rand(1, 100) <= 55) {
            $langs[] = 'Hindi';
        }

        $vegGroups = ['gujarati', 'jain'];
        $diet = in_array($groupKey, $vegGroups, true)
            ? $this->weighted(['vegetarian' => 60, 'jain' => $groupKey === 'jain' ? 30 : 2, 'eggetarian' => 15, 'non_vegetarian' => 10])
            : $this->weighted(['vegetarian' => 32, 'non_vegetarian' => 55, 'eggetarian' => 11, 'vegan' => 2]);
        if ($religion === 'Muslim' || $religion === 'Christian') {
            $diet = $this->weighted(['non_vegetarian' => 85, 'eggetarian' => 8, 'vegetarian' => 7]);
        }

        $job = mt_rand(1, 100) <= 78 ? $this->pick($this->data['jobs']) : ['', ''];
        $height = $gender === 'man' ? $this->normal(174, 6.5, 160, 192) : $this->normal(161, 6, 148, 178);

        $interests = $this->pickMany($this->data['interests'] ?? opt('interests'), mt_rand(4, 7));
        $prompts = [];
        $promptKeys = array_keys($this->data['prompt_answers']);
        $n = $this->weighted([0 => 12, 1 => 25, 2 => 38, 3 => 25]);
        foreach ($this->pickMany($promptKeys, $n) as $q) {
            $prompts[] = ['q' => $q, 'a' => $this->pick($this->data['prompt_answers'][$q])];
        }

        $interestedIn = $gender === 'man' ? 'women' : 'men';
        if (mt_rand(1, 100) <= 4) {
            $interestedIn = 'everyone';
        }
        $created = time() - mt_rand(3600, 86400 * 90);
        $verifiedPct = (int) ($opts['verified_pct'] ?? 40);

        return [
            'email'             => $this->email($first, $last),
            'password_hash'     => password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT, ['cost' => 4]),
            'first_name'        => $first,
            'last_name'         => $last,
            'gender'            => $gender,
            'interested_in'     => $interestedIn,
            'dob'               => $dob,
            'city'              => $cityName,
            'state'             => $state,
            'lat'               => $lat,
            'lng'               => $lng,
            'bio'               => mt_rand(1, 100) <= 85 ? $this->pick($this->data['bios']) : '',
            'job_title'         => $job[0],
            'company'           => $job[1],
            'education'         => mt_rand(1, 100) <= 75 ? $this->pick($this->data['education']) : '',
            'height_cm'         => mt_rand(1, 100) <= 80 ? $height : null,
            'religion'          => mt_rand(1, 100) <= 85 ? $religion : '',
            'mother_tongue'     => $tongue,
            'languages'         => implode(',', array_unique($langs)),
            'drinking'          => $this->weighted(['never' => 42, 'socially' => 42, 'regularly' => 5, 'sober' => 11]),
            'smoking'           => $this->weighted(['never' => 80, 'socially' => 11, 'regularly' => 3, 'quitting' => 6]),
            'diet'              => $diet,
            'looking_for'       => $this->weighted(['long_term' => 34, 'long_term_open' => 20, 'marriage' => 19, 'figuring_out' => 15, 'friends' => 7, 'short_term' => 5]),
            'interests'         => implode(',', $interests),
            'prompts'           => json_encode($prompts, JSON_UNESCAPED_UNICODE),
            'pref_age_min'      => max(18, $age - 5),
            'pref_age_max'      => min(70, $age + 6),
            'pref_distance_km'  => 0,
            'status'            => 'active',
            'is_seed'           => 1,
            'is_verified'       => mt_rand(1, 100) <= $verifiedPct ? 1 : 0,
            'email_verified_at' => date('Y-m-d H:i:s', $created),
            'onboarded_at'      => date('Y-m-d H:i:s', $created + 600),
            'last_active_at'    => date('Y-m-d H:i:s', time() - mt_rand(3600, 86400 * 5)),
            'created_at'        => date('Y-m-d H:i:s', $created),
            'updated_at'        => date('Y-m-d H:i:s', $created),
        ];
    }

    private function pickCity(array $only): array
    {
        $all = cities();
        if ($only) {
            $all = array_values(array_filter($all, static fn ($c) => in_array($c[0], $only, true)));
            return $all ? $this->pick($all) : $this->pick(cities());
        }
        $weights = [];
        foreach ($all as $i => $c) {
            $weights[$i] = in_array($c[0], self::METROS, true) ? 9 : (in_array($c[0], self::TIER2, true) ? 3 : 1);
        }
        return $all[$this->weighted($weights)];
    }

    /** Ages cluster in the mid-twenties, like real dating app audiences. */
    private function age(int $min, int $max): int
    {
        if ($min === $max) {
            return $min;
        }
        $mode = max($min, min($max, 26));
        $u = mt_rand() / mt_getrandmax();
        $f = ($mode - $min) / ($max - $min);
        $x = $u < $f
            ? $min + sqrt($u * ($max - $min) * ($mode - $min))
            : $max - sqrt((1 - $u) * ($max - $min) * ($max - $mode));
        return (int) round($x);
    }

    private function normal(float $mean, float $sd, int $min, int $max): int
    {
        $u = max(1e-9, mt_rand() / mt_getrandmax());
        $v = mt_rand() / mt_getrandmax();
        $z = sqrt(-2 * log($u)) * cos(2 * M_PI * $v);
        return (int) max($min, min($max, round($mean + $z * $sd)));
    }

    private function email(string $first, string $last): string
    {
        $base = strtolower(preg_replace('/[^a-z]/i', '', $first) . '.' . preg_replace('/[^a-z]/i', '', $last));
        return $base . mt_rand(100, 99999) . '@seed.truetrust.invalid';
    }

    private function pick(array $list)
    {
        return $list[array_rand($list)];
    }

    private function pickMany(array $list, int $n): array
    {
        if ($n <= 0) {
            return [];
        }
        $keys = (array) array_rand($list, min($n, count($list)));
        shuffle($keys);
        return array_map(static fn ($k) => $list[$k], $keys);
    }

    /** @param array<string|int,int> $weights */
    private function weighted(array $weights)
    {
        $total = array_sum($weights);
        $r = mt_rand(1, max(1, $total));
        foreach ($weights as $key => $w) {
            $r -= $w;
            if ($r <= 0) {
                return $key;
            }
        }
        return array_key_first($weights);
    }
}
