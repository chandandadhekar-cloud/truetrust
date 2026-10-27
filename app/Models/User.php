<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\Settings;

final class User
{
    public const ONBOARDING_STEPS = ['basics', 'identity', 'location', 'photos', 'about', 'prompts'];

    public static function find(int $id): ?array
    {
        return DB::one('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DB::one('SELECT * FROM users WHERE email = ?', [mb_strtolower(trim($email))]);
    }

    public static function create(array $data): int
    {
        $data += ['created_at' => now(), 'updated_at' => now()];
        $data['email'] = mb_strtolower(trim($data['email']));
        return DB::insert('users', $data);
    }

    public static function update(int $id, array $data): void
    {
        $data['updated_at'] = now();
        DB::update('users', $data, 'id = ?', [$id]);
    }

    public static function nextOnboardingStep(array $u): string
    {
        if ($u['first_name'] === '' || !$u['dob']) {
            return 'basics';
        }
        if (!$u['gender'] || !$u['interested_in']) {
            return 'identity';
        }
        if ($u['city'] === '') {
            return 'location';
        }
        if (Photo::countFor((int) $u['id']) === 0) {
            return 'photos';
        }
        if ($u['looking_for'] === '') {
            return 'about';
        }
        return 'prompts';
    }

    public static function interests(array $u): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $u['interests']))));
    }

    public static function languages(array $u): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $u['languages']))));
    }

    /** @return list<array{q:string,a:string}> */
    public static function prompts(array $u): array
    {
        $p = json_decode((string) ($u['prompts'] ?? ''), true);
        return is_array($p) ? array_values(array_filter($p, static fn ($x) => !empty($x['q']) && !empty($x['a']))) : [];
    }

    public static function distanceKm(?float $lat1, ?float $lng1, ?float $lat2, ?float $lng2): ?int
    {
        if ($lat1 === null || $lng1 === null || $lat2 === null || $lng2 === null) {
            return null;
        }
        $r = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
    }

    public static function distanceLabel(?int $km): string
    {
        if ($km === null) {
            return '';
        }
        if ($km < 2) {
            return 'Nearby';
        }
        return $km . ' km away';
    }

    /**
     * Public-facing profile for cards and profile pages. Never includes
     * email, surname, admin flags or anything a viewer shouldn't see.
     */
    public static function card(array $u, ?array $viewer = null, ?array $photoIds = null): array
    {
        $photoIds ??= Photo::idsFor((int) $u['id'], true);
        $km = $viewer ? self::distanceKm(
            $viewer['lat'] !== null ? (float) $viewer['lat'] : null,
            $viewer['lng'] !== null ? (float) $viewer['lng'] : null,
            $u['lat'] !== null ? (float) $u['lat'] : null,
            $u['lng'] !== null ? (float) $u['lng'] : null
        ) : null;

        $basics = [];
        if ($u['height_cm']) {
            $basics[] = ['icon' => 'ruler', 'label' => self::heightLabel((int) $u['height_cm'])];
        }
        if ($u['looking_for']) {
            $basics[] = ['icon' => 'target', 'label' => opt_label('looking_for', $u['looking_for'])];
        }
        if ($u['religion']) {
            $basics[] = ['icon' => 'sparkle', 'label' => $u['religion']];
        }
        if ($u['diet']) {
            $basics[] = ['icon' => 'leaf', 'label' => opt_label('diet', $u['diet'])];
        }
        if ($u['drinking']) {
            $basics[] = ['icon' => 'glass', 'label' => opt_label('drinking', $u['drinking'])];
        }
        if ($u['smoking']) {
            $basics[] = ['icon' => 'smoke', 'label' => opt_label('smoking', $u['smoking'])];
        }
        $langs = self::languages($u);
        if ($langs) {
            $basics[] = ['icon' => 'globe', 'label' => implode(', ', $langs)];
        }

        return [
            'id'        => (int) $u['id'],
            'name'      => $u['first_name'],
            'age'       => age($u['dob']),
            'city'      => $u['city'],
            'distance'  => self::distanceLabel($km),
            'verified'  => (bool) $u['is_verified'],
            'job'       => trim($u['job_title'] . ($u['company'] !== '' ? ' · ' . $u['company'] : '')),
            'education' => $u['education'],
            'bio'       => (string) $u['bio'],
            'basics'    => $basics,
            'interests' => self::interests($u),
            'prompts'   => self::prompts($u),
            'photos'    => array_map(static fn ($id) => photo_url((int) $id), $photoIds),
            'activity'  => activity_label($u['last_active_at']),
            'online'    => is_online($u['last_active_at']),
        ];
    }

    public static function heightLabel(int $cm): string
    {
        $inches = (int) round($cm / 2.54);
        return sprintf("%d′%d″ · %d cm", intdiv($inches, 12), $inches % 12, $cm);
    }

    /** Profile completeness 0–100, used to nudge members to improve their profile. */
    public static function completeness(array $u): int
    {
        $checks = [
            Photo::countFor((int) $u['id']) >= 3,
            mb_strlen((string) $u['bio']) >= 40,
            $u['job_title'] !== '',
            $u['education'] !== '',
            (bool) $u['height_cm'],
            $u['religion'] !== '',
            $u['languages'] !== '',
            count(self::interests($u)) >= 3,
            count(self::prompts($u)) >= 2,
            $u['drinking'] !== '' && $u['smoking'] !== '',
        ];
        return (int) round(100 * count(array_filter($checks)) / count($checks));
    }

    /** Frees the email address and removes personal data while keeping moderation history intact. */
    public static function softDelete(int $id): void
    {
        DB::query('DELETE FROM photos WHERE user_id = ?', [$id]);
        DB::query('DELETE FROM push_subscriptions WHERE user_id = ?', [$id]);
        DB::query('DELETE FROM notifications WHERE user_id = ?', [$id]);
        DB::query("UPDATE verification_requests SET selfie = '' WHERE user_id = ?", [$id]);
        DB::query('UPDATE matches SET unmatched_at = NOW(), unmatched_by = ? WHERE (user_a = ? OR user_b = ?) AND unmatched_at IS NULL', [$id, $id, $id]);
        self::update($id, [
            'status'     => 'deleted',
            'email'      => "deleted-{$id}-" . bin2hex(random_bytes(4)) . '@deleted.invalid',
            'bio'        => null,
            'prompts'    => null,
            'last_name'  => '',
            'is_hidden'  => 1,
        ]);
        \App\Core\Auth::revokeAll($id);
    }

    public static function minDob(): string
    {
        return date('Y-m-d', strtotime('-' . max(18, Settings::int('min_age')) . ' years'));
    }
}
