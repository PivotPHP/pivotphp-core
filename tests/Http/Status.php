<?php

declare(strict_types=1);

namespace PivotPHP\Core\Tests\Http;

enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
