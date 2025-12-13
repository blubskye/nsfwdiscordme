<?php
namespace App\Enum;

enum AppEventType: string
{
    case ADMIN_LOGIN = 'app.admin.login';
    case SERVER_JOIN = 'app.server.join';
    case SERVER_VIEW = 'app.server.view';
    case SERVER_BUMP = 'app.server.bump';
    case SERVER_ACTION = 'app.server.action';
}
