<?php
namespace App\Event;

use App\Enum\AppEventType;

/**
 * Event name constants - kept for backward compatibility.
 * Prefer using AppEventType enum directly.
 */
final class AppEvents
{
    public const ADMIN_LOGIN = 'app.admin.login';
    public const SERVER_JOIN = 'app.server.join';
    public const SERVER_VIEW = 'app.server.view';
    public const SERVER_BUMP = 'app.server.bump';
    public const SERVER_ACTION = 'app.server.action';

    public static function fromEnum(AppEventType $type): string
    {
        return $type->value;
    }
}
