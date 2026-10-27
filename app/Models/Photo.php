<?php
namespace App\Models;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\Image;
use App\Core\Settings;

final class Photo
{
    public static function forUser(int $userId, bool $publicOnly = false): array
    {
        $where = $publicOnly ? "AND status = 'approved'" : "AND status <> 'rejected'";
        return DB::all("SELECT id, user_id, position, status, width, height, created_at FROM photos WHERE user_id = ? $where ORDER BY position, id", [$userId]);
    }

    public static function idsFor(int $userId, bool $publicOnly = true): array
    {
        return array_map('intval', array_column(self::forUser($userId, $publicOnly), 'id'));
    }

    /** Batch lookup of public photo ids for many users: [userId => [photoId, ...]]. */
    public static function idsForMany(array $userIds): array
    {
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (!$userIds) {
            return [];
        }
        $rows = DB::all(
            'SELECT id, user_id FROM photos WHERE user_id IN (' . DB::placeholders($userIds) . ") AND status = 'approved' ORDER BY position, id",
            $userIds
        );
        $out = array_fill_keys($userIds, []);
        foreach ($rows as $r) {
            $out[(int) $r['user_id']][] = (int) $r['id'];
        }
        return $out;
    }

    public static function primaryId(int $userId, bool $publicOnly = true): ?int
    {
        return self::idsFor($userId, $publicOnly)[0] ?? null;
    }

    public static function countFor(int $userId): int
    {
        return (int) DB::value("SELECT COUNT(*) FROM photos WHERE user_id = ? AND status <> 'rejected'", [$userId]);
    }

    /** Stores an uploaded image for a user. $byAdmin skips moderation. */
    public static function store(int $userId, array $file, bool $byAdmin = false): int
    {
        $img = Image::fromUpload($file);
        return self::storeProcessed($userId, $img, $byAdmin);
    }

    public static function storeProcessed(int $userId, array $img, bool $byAdmin = false): int
    {
        $status = (!$byAdmin && Settings::bool('photo_moderation')) ? 'pending' : 'approved';
        return DB::transaction(static function () use ($userId, $img, $status) {
            $pos = (int) DB::value('SELECT COALESCE(MAX(position), -1) + 1 FROM photos WHERE user_id = ?', [$userId]);
            $id = DB::insert('photos', [
                'user_id'    => $userId,
                'position'   => $pos,
                'mime'       => $img['mime'],
                'width'      => $img['width'],
                'height'     => $img['height'],
                'status'     => $status,
                'created_at' => now(),
            ]);
            DB::insert('photo_blobs', ['photo_id' => $id, 'data' => $img['data'], 'thumb' => $img['thumb']]);
            return $id;
        });
    }

    public static function delete(int $photoId): void
    {
        DB::query('DELETE FROM photos WHERE id = ?', [$photoId]);
    }

    /** Applies a new order given a list of photo ids belonging to the user. */
    public static function reorder(int $userId, array $ids): void
    {
        $owned = array_map('intval', DB::column('SELECT id FROM photos WHERE user_id = ?', [$userId]));
        $pos = 0;
        foreach ($ids as $id) {
            $id = (int) $id;
            if (in_array($id, $owned, true)) {
                DB::query('UPDATE photos SET position = ? WHERE id = ?', [$pos++, $id]);
            }
        }
    }

    public static function makePrimary(int $userId, int $photoId): void
    {
        $ids = self::idsFor($userId, false);
        $ids = array_merge([$photoId], array_values(array_diff($ids, [$photoId])));
        self::reorder($userId, $ids);
    }
}
