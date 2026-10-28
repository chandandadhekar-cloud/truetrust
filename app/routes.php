<?php
defined('APP_ROOT') || exit;

use App\Controllers as C;
use App\Controllers\Admin as A;
use App\Core\Router;

return static function (Router $r): void {
    // Public & system
    $r->get('/photo/{id:\d+}/{size:full|thumb}', [C\MediaController::class, 'photo']);
    $r->get('/manifest.webmanifest', [C\PwaController::class, 'manifest']);
    $r->get('/offline', [C\PwaController::class, 'offline']);
    $r->get('/terms', [C\PageController::class, 'terms']);
    $r->get('/privacy', [C\PageController::class, 'privacy']);
    $r->get('/safety', [C\PageController::class, 'safety']);
    $r->get('/guidelines', [C\PageController::class, 'guidelines']);

    $r->group('', ['open'], static function (Router $r) {
        $r->get('/', [C\HomeController::class, 'index']);

        // Guests
        $r->group('', ['guest'], static function (Router $r) {
            $r->get('/signup', [C\AuthController::class, 'signupForm']);
            $r->post('/signup', [C\AuthController::class, 'signup']);
            $r->get('/login', [C\AuthController::class, 'loginForm']);
            $r->post('/login', [C\AuthController::class, 'login']);
            $r->get('/forgot-password', [C\AuthController::class, 'forgotForm']);
            $r->post('/forgot-password', [C\AuthController::class, 'forgot']);
            $r->get('/reset-password/{token}', [C\AuthController::class, 'resetForm']);
            $r->post('/reset-password/{token}', [C\AuthController::class, 'reset']);
        });
        $r->get('/verify/{token}', [C\AuthController::class, 'verify']);

        // Signed in (any state)
        $r->group('', ['auth'], static function (Router $r) {
            $r->post('/logout', [C\AuthController::class, 'logout']);
            $r->get('/account-restricted', [C\AuthController::class, 'restricted']);
            $r->get('/verify-email', [C\AuthController::class, 'verifyNotice']);
            $r->post('/verify-email', [C\AuthController::class, 'resendVerification']);
            $r->get('/onboarding/{step}', [C\OnboardingController::class, 'show']);
            $r->post('/onboarding/{step}', [C\OnboardingController::class, 'save']);
            $r->post('/api/photos', [C\PhotoController::class, 'upload']);
            $r->post('/api/photos/{id:\d+}/delete', [C\PhotoController::class, 'delete']);
            $r->post('/api/photos/reorder', [C\PhotoController::class, 'reorder']);
        });

        // Fully set-up members
        $r->group('', ['auth', 'member'], static function (Router $r) {
            $r->get('/discover', [C\DiscoverController::class, 'index']);
            $r->get('/api/deck', [C\DiscoverController::class, 'deck']);
            $r->post('/api/swipe', [C\DiscoverController::class, 'swipe']);
            $r->post('/api/rewind', [C\DiscoverController::class, 'rewind']);
            $r->post('/api/filters', [C\DiscoverController::class, 'filters']);

            $r->get('/likes', [C\LikesController::class, 'index']);
            $r->get('/u/{id:\d+}', [C\ProfileController::class, 'show']);

            $r->get('/matches', [C\ChatController::class, 'index']);
            $r->get('/chat/{id:\d+}', [C\ChatController::class, 'show']);
            $r->get('/api/chat/{id:\d+}', [C\ChatController::class, 'poll']);
            $r->post('/api/chat/{id:\d+}', [C\ChatController::class, 'send']);
            $r->post('/chat/{id:\d+}/unmatch', [C\ChatController::class, 'unmatch']);

            $r->post('/api/report', [C\SafetyController::class, 'report']);
            $r->post('/api/block', [C\SafetyController::class, 'block']);
            $r->get('/api/badges', [C\HomeController::class, 'badges']);
            $r->post('/api/push/subscribe', [C\PushController::class, 'subscribe']);
            $r->post('/api/push/unsubscribe', [C\PushController::class, 'unsubscribe']);
            $r->get('/api/push/pending', [C\PushController::class, 'pending']);
            $r->post('/settings/notifications', [C\PushController::class, 'preferences']);

            $r->get('/profile', [C\ProfileController::class, 'mine']);
            $r->get('/verify-profile', [C\VerificationController::class, 'show']);
            $r->post('/verify-profile', [C\VerificationController::class, 'submit']);
            $r->get('/profile/edit', [C\ProfileController::class, 'edit']);
            $r->post('/profile/edit', [C\ProfileController::class, 'update']);
            $r->get('/settings', [C\SettingsController::class, 'index']);
            $r->post('/settings/preferences', [C\SettingsController::class, 'preferences']);
            $r->post('/settings/password', [C\SettingsController::class, 'password']);
            $r->post('/settings/email', [C\SettingsController::class, 'email']);
            $r->post('/settings/delete', [C\SettingsController::class, 'delete']);
            $r->get('/settings/blocked', [C\SettingsController::class, 'blocked']);
            $r->post('/settings/unblock/{id:\d+}', [C\SettingsController::class, 'unblock']);
        });
    });

    // Admin
    $r->get('/admin/login', [A\AuthController::class, 'loginForm']);
    $r->post('/admin/login', [A\AuthController::class, 'login']);
    $r->group('/admin', ['admin'], static function (Router $r) {
        $r->post('/logout', [A\AuthController::class, 'logout']);
        $r->get('/password', [A\AuthController::class, 'passwordForm']);
        $r->post('/password', [A\AuthController::class, 'password']);

        $r->get('', [A\DashboardController::class, 'index']);

        $r->get('/users', [A\UsersController::class, 'index']);
        $r->get('/users/export', [A\UsersController::class, 'export'], ['owner']);
        $r->get('/users/new', [A\UsersController::class, 'create']);
        $r->post('/users/new', [A\UsersController::class, 'store']);
        $r->get('/users/{id:\d+}', [A\UsersController::class, 'show']);
        $r->post('/users/{id:\d+}', [A\UsersController::class, 'update']);
        $r->post('/users/{id:\d+}/action', [A\UsersController::class, 'action']);
        $r->post('/users/{id:\d+}/photos', [A\UsersController::class, 'uploadPhotos']);
        $r->post('/users/bulk', [A\UsersController::class, 'bulk']);

        $r->get('/seed', [A\SeedController::class, 'index']);
        $r->post('/seed/generate', [A\SeedController::class, 'generate']);
        $r->post('/seed/photos', [A\SeedController::class, 'assignPhotos']);
        $r->post('/seed/purge', [A\SeedController::class, 'purge'], ['owner']);
        $r->post('/seed/ai/queue', [A\SeedController::class, 'aiQueue']);
        $r->post('/seed/ai/run', [A\SeedController::class, 'aiRun']);
        $r->get('/seed/ai/status', [A\SeedController::class, 'aiStatus']);
        $r->post('/seed/ai/manage', [A\SeedController::class, 'aiManage']);
        $r->post('/users/{id:\d+}/ai-photos', [A\SeedController::class, 'aiQueueOne']);

        $r->get('/conversations', [A\ConversationsController::class, 'index']);
        $r->get('/conversations/{id:\d+}', [A\ConversationsController::class, 'show']);
        $r->get('/messages/flagged', [A\ConversationsController::class, 'flagged']);
        $r->post('/messages/{id:\d+}', [A\ConversationsController::class, 'moderate']);

        $r->get('/reports', [A\ReportsController::class, 'index']);
        $r->post('/reports/{id:\d+}', [A\ReportsController::class, 'resolve']);

        $r->get('/verifications', [A\VerificationsController::class, 'index']);
        $r->get('/verifications/{id:\d+}/selfie', [A\VerificationsController::class, 'selfie']);
        $r->post('/verifications/{id:\d+}', [A\VerificationsController::class, 'review']);

        $r->get('/photos', [A\PhotosController::class, 'index']);
        $r->post('/photos/{id:\d+}', [A\PhotosController::class, 'moderate']);

        $r->get('/settings', [A\SettingsController::class, 'index']);
        $r->post('/settings', [A\SettingsController::class, 'save']);
        $r->post('/settings/test-email', [A\SettingsController::class, 'testEmail']);

        $r->get('/team', [A\TeamController::class, 'index']);
        $r->post('/team', [A\TeamController::class, 'store'], ['owner']);
        $r->post('/team/{id:\d+}', [A\TeamController::class, 'update'], ['owner']);

        $r->get('/logs', [A\DashboardController::class, 'logs']);
        $r->get('/system', [A\DashboardController::class, 'system']);
    });
};
