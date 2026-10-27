<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\DB;
use App\Core\Image;
use App\Core\RateLimit;
use App\Core\Request;
use App\Core\Session;

/**
 * Selfie verification: the member copies a randomly chosen pose, a moderator
 * compares it with their profile photos and grants the verified badge.
 */
final class VerificationController extends Controller
{
    public const POSES = [
        'peace'   => 'Make a peace sign next to your face',
        'thumbs'  => 'Give a thumbs up next to your face',
        'chin'    => 'Rest your chin on your fist',
        'point'   => 'Point at the camera with one finger',
        'eye'     => 'Cover one eye with your hand',
        'ear'     => 'Touch your ear with one hand',
        'wave'    => 'Wave at the camera with an open hand',
    ];

    public function show(): string
    {
        $me = $this->me();
        $latest = DB::one(
            'SELECT id, pose, status, note, created_at, reviewed_at FROM verification_requests WHERE user_id = ? ORDER BY id DESC LIMIT 1',
            [$me['id']]
        );
        $pose = Session::get('verify_pose');
        if (!isset(self::POSES[$pose])) {
            $pose = array_rand(self::POSES);
            Session::put('verify_pose', $pose);
        }
        return view('profile/verify', [
            'title'   => 'Get verified',
            'nav'     => true,
            'me'      => $me,
            'latest'  => $latest,
            'pose'    => $pose,
            'poseText' => self::POSES[$pose],
        ]);
    }

    public function submit(): void
    {
        $me = $this->me();
        if ($me['is_verified']) {
            redirect('/verify-profile');
        }
        if (DB::value("SELECT 1 FROM verification_requests WHERE user_id = ? AND status = 'pending'", [$me['id']])) {
            flash('info', 'Your selfie is already being reviewed.');
            redirect('/verify-profile');
        }
        if (!RateLimit::hit('verify-selfie:' . $me['id'], 5, 86400)) {
            flash('error', 'You have tried a few times today. Please try again tomorrow.');
            redirect('/verify-profile');
        }
        $file = Request::file('selfie');
        $pose = Session::get('verify_pose');
        if (!$file || !isset(self::POSES[$pose])) {
            flash('error', 'Take a selfie to continue.');
            redirect('/verify-profile');
        }
        try {
            $img = Image::fromUpload($file);
        } catch (\RuntimeException $e) {
            flash('error', $e->getMessage());
            redirect('/verify-profile');
        }
        DB::insert('verification_requests', [
            'user_id'    => $me['id'],
            'pose'       => $pose,
            'selfie'     => $img['data'],
            'mime'       => $img['mime'],
            'created_at' => now(),
        ]);
        Session::forget('verify_pose');
        flash('success', "Selfie sent. We'll review it shortly.");
        redirect('/verify-profile');
    }
}
