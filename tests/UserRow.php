<?php

namespace Waypoint\Export\Tests;

use Waypoint\Export\{CellType, Column};

final class UserRow
{
    #[Column('ID', type: CellType::Number)]
    public int $id = 7;

    public string $secret = 'never exported';

    #[Column('Name', name: 'fullName')]
    public string $name = 'Ada';
}
