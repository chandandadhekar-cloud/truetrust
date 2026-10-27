<?php
namespace App\Core;

defined('APP_ROOT') || exit;

final class View
{
    /** Renders app/Views/{$view}.php, optionally wrapped in a layout that receives $content. */
    public static function render(string $view, array $data = [], ?string $layout = null): string
    {
        $content = self::capture($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::capture($layout, $data + ['content' => $content]);
    }

    public static function partial(string $view, array $data = []): string
    {
        return self::capture($view, $data);
    }

    private static function capture(string $__view, array $__data): string
    {
        $__file = APP_ROOT . '/app/Views/' . $__view . '.php';
        if (!is_file($__file)) {
            throw new \RuntimeException("View not found: {$__view}");
        }
        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string) ob_get_clean();
    }
}
