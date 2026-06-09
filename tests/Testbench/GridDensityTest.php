<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\Admin;
use Dcat\Admin\Grid;
use Dcat\Admin\Tests\Testbench\Fixtures\NullRepository;

class GridDensityTest extends TestCase
{
    protected function makeGrid(): Grid
    {
        return new Grid(new NullRepository());
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Reset asset styles between tests to avoid cross-test pollution
        Admin::asset()->style = [];
    }

    // Task 1: compact() tests

    public function test_compact_is_chainable(): void
    {
        $grid = $this->makeGrid();
        $this->assertSame($grid, $grid->compact());
    }

    public function test_compact_off_by_default(): void
    {
        $this->assertStringNotContainsString('dcat-grid-compact', $this->makeGrid()->formatTableClass());
    }

    public function test_compact_adds_class_when_enabled(): void
    {
        $grid = $this->makeGrid();
        $grid->compact();
        $this->assertStringContainsString('dcat-grid-compact', $grid->formatTableClass());
    }

    // Task 2: stickyHeader() tests

    public function test_sticky_header_is_chainable(): void
    {
        $grid = $this->makeGrid();
        $this->assertSame($grid, $grid->stickyHeader());
    }

    public function test_sticky_header_off_by_default(): void
    {
        $this->assertStringNotContainsString('dcat-grid-sticky-header', $this->makeGrid()->formatTableClass());
    }

    public function test_sticky_header_adds_class_when_enabled(): void
    {
        $grid = $this->makeGrid();
        $grid->stickyHeader();
        $this->assertStringContainsString('dcat-grid-sticky-header', $grid->formatTableClass());
    }

    // Task 3: CSS injection tests

    protected function makeRenderableGrid(): Grid
    {
        $grid = $this->makeGrid();
        $grid->model()->setData([]);
        $grid->disablePagination();

        return $grid;
    }

    public function test_compact_injects_css_on_render(): void
    {
        $grid = $this->makeRenderableGrid();
        $grid->compact();
        $grid->render();
        $this->assertStringContainsString('.dcat-grid-compact', Admin::asset()->styleToHtml());
    }

    public function test_sticky_injects_css_on_render(): void
    {
        $grid = $this->makeRenderableGrid();
        $grid->stickyHeader();
        $grid->render();
        $this->assertStringContainsString('position: sticky', Admin::asset()->styleToHtml());
    }

    public function test_no_density_css_when_disabled(): void
    {
        $this->makeRenderableGrid()->render();
        $css = Admin::asset()->styleToHtml();
        $this->assertStringNotContainsString('.dcat-grid-compact', $css);
        $this->assertStringNotContainsString('.dcat-grid-sticky-header', $css);
    }
}
