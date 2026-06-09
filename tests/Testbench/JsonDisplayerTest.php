<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\Grid\Column;
use Dcat\Admin\Tests\Testbench\Fixtures\NullRepository;
use ReflectionClass;

class JsonDisplayerTest extends TestCase
{
    public function test_json_displayer_is_registered(): void
    {
        $ref = new ReflectionClass(Column::class);
        $prop = $ref->getProperty('displayers');
        $prop->setAccessible(true);
        $map = $prop->getValue();
        $this->assertArrayHasKey('json', $map);
        $this->assertSame(\Dcat\Admin\Grid\Displayers\Json::class, $map['json']);
    }

    public function test_displayer_renders_pretty_pre_for_array(): void
    {
        $displayer = new \Dcat\Admin\Grid\Displayers\Json(['a' => 1], $this->fakeGrid(), $this->fakeColumn(), (object) []);
        $html = $displayer->display();
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('"a": 1', $html);
    }

    public function test_displayer_safe_on_null_and_invalid(): void
    {
        $g = $this->fakeGrid();
        $c = $this->fakeColumn();
        $this->assertSame('', (new \Dcat\Admin\Grid\Displayers\Json(null, $g, $c, (object) []))->display());
        $invalid = (new \Dcat\Admin\Grid\Displayers\Json('{not json', $g, $c, (object) []))->display();
        $this->assertStringContainsString('{not json', $invalid);
    }

    protected function fakeGrid(): \Dcat\Admin\Grid
    {
        return new \Dcat\Admin\Grid(new NullRepository());
    }

    protected function fakeColumn(): Column
    {
        return new Column('specs', '规格');
    }
}
