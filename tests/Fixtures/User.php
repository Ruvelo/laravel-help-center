<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property bool $is_editor
 */
class User extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];
}
