<?php

namespace App\Enums;

enum UserRoleEnum: string
{
    case ADMIN = 'admin';
    case LIBRARIAN = 'librarian';
    case READER = 'reader';
}
