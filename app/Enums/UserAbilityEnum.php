<?php

namespace App\Enums;

enum UserAbilityEnum: string
{
    case VIEW_ANY = 'viewAny';
    case VIEW = 'view';
    case CREATE = 'create';
    case UPDATE = 'update';
    case DELETE = 'delete';

    case ISSUE_BOOKS = 'issue_books';
}
