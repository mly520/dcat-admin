<?php

namespace Dcat\Admin\Tests\Testbench;

class CoreComponentsTest extends TestCase
{
    public function test_core_classes_exist(): void
    {
        $this->assertTrue(class_exists(\Dcat\Admin\Grid::class));
        $this->assertTrue(class_exists(\Dcat\Admin\Form::class));
        $this->assertTrue(class_exists(\Dcat\Admin\Show::class));
    }

    public function test_form_can_be_instantiated(): void
    {
        $form = new \Dcat\Admin\Form(new \Dcat\Admin\Tests\Testbench\Fixtures\NullRepository());

        $this->assertInstanceOf(\Dcat\Admin\Form::class, $form);
    }
}
