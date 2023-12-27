<?php

namespace App\Enums;

enum UserAbilityEnum: string
{
    case LIST = 'list';
    case VIEW = 'view';
    case CREATE = 'create';
    case EDIT = 'edit';
    case DELETE = 'delete';

    case ISSUE_BOOKS = 'issue_books';
}
