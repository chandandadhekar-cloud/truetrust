<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

final class PwaController extends Controller
{
    public function manifest(): string
    {
        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        $name = setting('site_name');
        return json_encode([
            'id'               => '/',
            'name'             => $name . ' — Free dating',
            'short_name'       => $name,
            'description'      => 'Free dating for India. See who likes you and chat without limits.',
            'start_url'        => '/discover?source=pwa',
            'scope'            => '/',
            'display'          => 'standalone',
            'orientation'      => 'portrait',
            'background_color' => '#FBF8F4',
            'theme_color'      => '#FBF8F4',
            'categories'       => ['social', 'lifestyle'],
            'icons'            => [
                ['src' => '/assets/img/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/assets/img/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any'],
                ['src' => '/assets/img/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
            ],
            'shortcuts'        => [
                ['name' => 'Discover', 'url' => '/discover', 'icons' => [['src' => '/assets/img/icon-192.png', 'sizes' => '192x192']]],
                ['name' => 'Chats', 'url' => '/matches', 'icons' => [['src' => '/assets/img/icon-192.png', 'sizes' => '192x192']]],
                ['name' => 'Likes', 'url' => '/likes', 'icons' => [['src' => '/assets/img/icon-192.png', 'sizes' => '192x192']]],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    public function offline(): string
    {
        return view('pages/offline', ['title' => 'Offline'], 'layouts/app');
    }
}
