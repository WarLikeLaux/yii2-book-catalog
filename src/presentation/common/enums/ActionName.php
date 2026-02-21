<?php

declare(strict_types=1);

namespace app\presentation\common\enums;

enum ActionName: string
{
    case INDEX = 'index';
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case PUBLISH = 'publish';
    case UNPUBLISH = 'unpublish';
    case ARCHIVE = 'archive';
    case RESTORE = 'restore';
    case LOGOUT = 'logout';
    case LOGIN = 'login';
    case SUBSCRIBE = 'subscribe';
    case FORM = 'form';
    case SEARCH = 'search';
    case API = 'api';
}
