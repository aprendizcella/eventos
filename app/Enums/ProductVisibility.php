<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductVisibility: string
{
    case Public = 'public';
    case Hidden = 'hidden';
    case Password = 'password';
}
