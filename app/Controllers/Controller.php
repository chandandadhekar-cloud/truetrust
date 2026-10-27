<?php
namespace App\Controllers;

defined('APP_ROOT') || exit;

use App\Core\Auth;
use App\Core\HttpException;

abstract class Controller
{
    protected function me(): array
    {
        $user = Auth::user();
        if (!$user) {
            throw new HttpException(401, 'Please sign in again.');
        }
        return $user;
    }

    /** Throws 422 with the first message when validation fails (for JSON endpoints). */
    protected function fail(string $message, int $status = 422): void
    {
        throw new HttpException($status, $message);
    }
}
