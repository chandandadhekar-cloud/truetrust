<?php
namespace App\Core;

defined('APP_ROOT') || exit;

use App\Models\User;

final class App
{
    public static function run(): void
    {
        self::securityHeaders();
        try {
            Session::start();
            Migrator::ensure();
            Auth::boot();

            $router = new Router();
            (require APP_ROOT . '/app/routes.php')($router);
            $route = $router->match(Request::method(), Request::path());

            if (Request::isPost()) {
                Csrf::verify();
            }
            foreach ($route['middleware'] as $mw) {
                self::middleware($mw);
            }

            [$class, $method] = $route['handler'];
            $params = array_map(static fn ($v) => ctype_digit($v) ? (int) $v : $v, array_values($route['params']));
            $result = (new $class())->$method(...$params);
            if (is_string($result)) {
                echo $result;
            }
        } catch (HttpException $e) {
            self::renderError($e->status, $e->getMessage());
        } catch (\PDOException $e) {
            error_log('DB error: ' . $e->getMessage());
            self::renderError(503, Config::get('app.debug') ? $e->getMessage() : '');
        } catch (\Throwable $e) {
            error_log((string) $e);
            self::renderError(500, Config::get('app.debug') ? $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine() : '');
        }
    }

    private static function middleware(string $name): void
    {
        $path = Request::path();
        switch ($name) {
            case 'guest':
                if (Auth::check()) {
                    redirect('/discover');
                }
                break;

            case 'auth':
                $user = Auth::user();
                if (!$user) {
                    if (Request::wantsJson()) {
                        throw new HttpException(401, 'Please sign in again.');
                    }
                    Session::put('intended', $path);
                    redirect('/login');
                }
                if (in_array($user['status'], ['banned', 'suspended'], true)) {
                    if ($path !== '/account-restricted') {
                        redirect('/account-restricted');
                    }
                }
                break;

            case 'member':
                // Fully set-up account: verified email (when required) and finished onboarding.
                $user = Auth::user();
                if (!$user) {
                    break;
                }
                if (Settings::bool('email_verification') && !$user['email_verified_at']) {
                    if (Request::wantsJson()) {
                        throw new HttpException(403, 'Please verify your email first.');
                    }
                    redirect('/verify-email');
                }
                if (!$user['onboarded_at']) {
                    if (Request::wantsJson()) {
                        throw new HttpException(403, 'Finish setting up your profile first.');
                    }
                    redirect('/onboarding/' . User::nextOnboardingStep($user));
                }
                break;

            case 'open':
                if (Settings::bool('maintenance_mode') && !AdminAuth::admin()) {
                    throw new HttpException(503, Settings::get('maintenance_message'));
                }
                break;

            case 'admin':
                $admin = AdminAuth::admin();
                if (!$admin) {
                    if (Request::wantsJson()) {
                        throw new HttpException(401);
                    }
                    redirect('/admin/login');
                }
                if ($admin['must_change_password'] && $path !== '/admin/password') {
                    redirect('/admin/password');
                }
                break;

            case 'owner':
                if (!AdminAuth::isOwner()) {
                    throw new HttpException(403, 'Only the owner account can do that.');
                }
                break;
        }
    }

    private static function securityHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(self), microphone=(), geolocation=(self)');
        if (Request::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    public static function renderError(int $status, string $message = ''): void
    {
        if (!headers_sent()) {
            http_response_code($status);
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $titles = [
            401 => 'Please sign in',
            403 => 'Not allowed',
            404 => 'Page not found',
            405 => 'Not allowed',
            419 => 'Session expired',
            422 => 'Check your details',
            429 => 'Slow down a little',
            500 => 'Something went wrong',
            503 => 'Be right back',
        ];
        $title = $titles[$status] ?? 'Error';
        if (Request::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => $message ?: $title]);
            return;
        }
        try {
            echo View::render('errors/error', ['status' => $status, 'title' => $title, 'message' => $message]);
        } catch (\Throwable $e) {
            echo '<h1>' . htmlspecialchars($title) . '</h1>';
        }
    }
}
