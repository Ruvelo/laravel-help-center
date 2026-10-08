<?php

declare(strict_types=1);

namespace Ruvelo\HelpCenter\Tests\Feature;

use Ruvelo\HelpCenter\Tests\TestCase;

class JsonApiOffTest extends TestCase
{
    public function test_the_json_api_is_off_by_default(): void
    {
        $this->assertFalse(app('router')->has('help.api.categories.index'));
        $this->getJson('/api/help/categories')->assertNotFound();
    }
}
