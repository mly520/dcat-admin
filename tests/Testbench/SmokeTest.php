<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\AdminServiceProvider;
use Dcat\Admin\Color;

class SmokeTest extends TestCase
{
    public function test_admin_service_provider_is_loaded(): void
    {
        $this->assertArrayHasKey(
            AdminServiceProvider::class,
            $this->app->getLoadedProviders()
        );
    }

    public function test_color_value_object_resolves(): void
    {
        $color = new Color();

        $this->assertInstanceOf(Color::class, $color);
    }
}
