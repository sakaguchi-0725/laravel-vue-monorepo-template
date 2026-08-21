<?php

declare(strict_types=1);

namespace App\Models;

enum TodoStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
}
