<?php

declare(strict_types=1);

namespace App\Enums;

enum EventVisibility: string
{
    case Private = 'private';
    case Public = 'public';
    case PasswordProtected = 'password_protected';
}
