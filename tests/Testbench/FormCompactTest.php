<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Admin;
use Dcat\Admin3\Form;
use Dcat\Admin3\Tests\Testbench\Fixtures\NullRepository;

class FormCompactTest extends TestCase
{
    protected function makeForm(): Form
    {
        return new Form(new NullRepository());
    }

    protected function setUp(): void
    {
        parent::setUp();
        // Reset asset styles between tests to avoid cross-test pollution
        Admin::asset()->style = [];
    }

    // Task 1: compact() flag + class injection

    public function test_compact_is_chainable(): void
    {
        $form = $this->makeForm();
        $this->assertSame($form, $form->compact());
    }

    public function test_builder_compact_flag(): void
    {
        $form = $this->makeForm();
        $form->compact();
        $this->assertTrue($form->builder()->isCompact());
    }

    public function test_compact_off_by_default(): void
    {
        $this->assertFalse($this->makeForm()->builder()->isCompact());
    }

    // Task 2: CSS injection on render

    public function test_compact_injects_css_on_render(): void
    {
        $form = $this->makeForm();
        $form->text('name');
        $form->compact();
        $form->builder()->render();
        $this->assertStringContainsString('.dcat-form-compact', Admin::asset()->styleToHtml());
    }

    public function test_no_css_when_not_compact(): void
    {
        $form = $this->makeForm();
        $form->text('name');
        $form->builder()->render();
        $this->assertStringNotContainsString('.dcat-form-compact', Admin::asset()->styleToHtml());
    }
}
