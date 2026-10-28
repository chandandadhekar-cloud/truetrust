<?php
namespace App\Services;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\Settings;
use App\Models\Photo;
use App\Models\User;

/**
 * Builds the swipe deck: who a member can see, in what order.
 */
final class Discovery
{
    private const GENDER_FOR = ['men' => ['man'], 'women' => ['woman'], 'everyone' => ['man', 'woman', 'nonbinary']];
    private const WANTS_ME = ['man' => ['men', 'everyone'], 'woman' => ['women', 'everyone'], 'nonbinary' => ['everyone']];

    public static function deck(array $me, int $limit = 10, array $exclude = []): array
    {
        $rows = self::query($me, $limit, $exclude);
        $photos = Photo::idsForMany(array_column($rows, 'id'));
        $ai = Photo::aiUsers(array_column($rows, 'id'));
        return array_map(static function ($u) use ($me, $photos, $ai) {
            $card = User::card($u, $me, $photos[(int) $u['id']] ?? [], isset($ai[(int) $u['id']]));
            $card['liked_you'] = (bool) $u['liked_me'];
            $card['superliked_you'] = $u['liked_me_action'] === 'superlike';
            return $card;
        }, $rows);
    }

    private static function query(array $me, int $limit, array $exclude): array
    {
        $myId = (int) $me['id'];
        $wantGenders = self::GENDER_FOR[$me['interested_in']] ?? self::GENDER_FOR['everyone'];
        $theyWant = self::WANTS_ME[$me['gender']] ?? ['everyone'];

        $minAge = max((int) Settings::get('min_age', 18), (int) $me['pref_age_min']);
        $maxAge = max($minAge, (int) $me['pref_age_max']);
        $youngestDob = date('Y-m-d', strtotime("-{$minAge} years"));
        $oldestDob = date('Y-m-d', strtotime('-' . ($maxAge + 1) . ' years +1 day'));

        $hasCoords = $me['lat'] !== null && $me['lng'] !== null;
        $distanceSql = $hasCoords
            ? '(6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(u.lat)) * COS(RADIANS(u.lng) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(u.lat)))))'
            : 'NULL';

        $params = [];
        if ($hasCoords) {
            array_push($params, (float) $me['lat'], (float) $me['lng'], (float) $me['lat']);
        }
        $params[] = $myId;

        $sql = "SELECT u.*, {$distanceSql} AS distance_km,
                    (SELECT s2.action FROM swipes s2 WHERE s2.user_id = u.id AND s2.target_id = ? AND s2.action IN ('like','superlike')) AS liked_me_action
                FROM users u
                WHERE u.status = 'active'
                  AND u.onboarded_at IS NOT NULL
                  AND u.is_hidden = 0
                  AND u.id <> ?
                  AND u.gender IN (" . DB::placeholders($wantGenders) . ')
                  AND u.interested_in IN (' . DB::placeholders($theyWant) . ')
                  AND u.dob <= ? AND u.dob >= ?
                  AND NOT EXISTS (SELECT 1 FROM swipes s WHERE s.user_id = ? AND s.target_id = u.id)
                  AND NOT EXISTS (SELECT 1 FROM blocks b WHERE (b.user_id = ? AND b.blocked_id = u.id) OR (b.user_id = u.id AND b.blocked_id = ?))';
        $params[] = $myId;
        array_push($params, ...$wantGenders);
        array_push($params, ...$theyWant);
        array_push($params, $youngestDob, $oldestDob, $myId, $myId, $myId);

        if (!Settings::bool('show_seed_profiles')) {
            $sql .= ' AND u.is_seed = 0';
        }
        if (Settings::bool('require_photo')) {
            $sql .= " AND EXISTS (SELECT 1 FROM photos p WHERE p.user_id = u.id AND p.status = 'approved')";
        }
        if (self::spotlightActive()) {
            $sql .= ' AND u.is_spotlight = 1';
        }
        $exclude = array_values(array_filter(array_map('intval', $exclude)));
        if ($exclude) {
            $sql .= ' AND u.id NOT IN (' . DB::placeholders($exclude) . ')';
            array_push($params, ...$exclude);
        }
        $pref = (int) $me['pref_distance_km'];
        if ($hasCoords && $pref > 0) {
            $sql .= ' HAVING (distance_km IS NULL OR distance_km <= ?)';
            $params[] = $pref;
        }

        // Boosted profiles first, then people who already liked you, then recently
        // active people close by, with a little randomness so decks don't feel static.
        $sql .= ' ORDER BY u.is_featured DESC,
                    (liked_me_action IS NOT NULL AND RAND() < 0.6) DESC,
                    (u.last_active_at > NOW() - INTERVAL 3 DAY) DESC,
                    ' . ($hasCoords ? 'FLOOR(COALESCE(distance_km, 5000) / 50)' : '0') . ',
                    RAND()
                  LIMIT ' . max(1, min(30, $limit));

        $rows = DB::all($sql, $params);
        foreach ($rows as &$r) {
            $r['liked_me'] = $r['liked_me_action'] !== null;
        }
        return $rows;
    }

    public static function spotlightActive(): bool
    {
        static $active = null;
        if ($active === null) {
            $active = (bool) DB::value("SELECT 1 FROM users WHERE is_spotlight = 1 AND status = 'active' AND is_hidden = 0 LIMIT 1");
        }
        return $active;
    }
}
