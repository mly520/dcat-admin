<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Grid\Column;
use Dcat\Admin3\Tests\Testbench\Fixtures\NullRepository;
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
        $this->assertSame(\Dcat\Admin3\Grid\Displayers\Json::class, $map['json']);
    }

    public function test_displayer_renders_pretty_pre_for_array(): void
    {
        $displayer = new \Dcat\Admin3\Grid\Displayers\Json(['a' => 1], $this->fakeGrid(), $this->fakeColumn(), (object) []);
        $html = $displayer->display();
        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('"a": 1', $html);
    }

    public function test_displayer_safe_on_null_and_invalid(): void
    {
        $g = $this->fakeGrid();
        $c = $this->fakeColumn();
        $this->assertSame('', (new \Dcat\Admin3\Grid\Displayers\Json(null, $g, $c, (object) []))->display());
        $invalid = (new \Dcat\Admin3\Grid\Displayers\Json('{not json', $g, $c, (object) []))->display();
        $this->assertStringContainsString('{not json', $invalid);
    }

    public function test_displayer_escapes_html_in_values(): void
    {
        $displayer = new \Dcat\Admin3\Grid\Displayers\Json(
            ['x' => '<script>alert(1)</script>'],
            $this->fakeGrid(),
            $this->fakeColumn(),
            (object) []
        );

        $html = $displayer->display();

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_displayer_falls_back_on_unencodable_value(): void
    {
        // 无效 UTF-8 序列让 json_encode 返回 false,应降级为原值转义而非空 <pre>
        $displayer = new \Dcat\Admin3\Grid\Displayers\Json(
            ["bad\xB1\x31value"],
            $this->fakeGrid(),
            $this->fakeColumn(),
            (object) []
        );

        $html = $displayer->display();

        $this->assertNotSame('', $html);
        $this->assertStringNotContainsString('<pre', $html);
    }

    protected function fakeGrid(): \Dcat\Admin3\Grid
    {
        return new \Dcat\Admin3\Grid(new NullRepository());
    }

    protected function fakeColumn(): Column
    {
        return new Column('specs', '规格');
    }
}
