<?php

namespace Dcat\Admin3\Tests\Testbench;

class CoreComponentsTest extends TestCase
{
    public function test_core_classes_exist(): void
    {
        $this->assertTrue(class_exists(\Dcat\Admin3\Grid::class));
        $this->assertTrue(class_exists(\Dcat\Admin3\Form::class));
        $this->assertTrue(class_exists(\Dcat\Admin3\Show::class));
    }

    public function test_form_can_be_instantiated(): void
    {
        $form = new \Dcat\Admin3\Form(new \Dcat\Admin3\Tests\Testbench\Fixtures\NullRepository());

        $this->assertInstanceOf(\Dcat\Admin3\Form::class, $form);
    }
}
