<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\Form;
use Dcat\Admin\Form\Field\Json;
use ReflectionClass;

class JsonFieldTest extends TestCase
{
    public function test_json_field_is_registered(): void
    {
        $ref = new ReflectionClass(Form::class);
        $prop = $ref->getProperty('availableFields');
        $prop->setAccessible(true);
        $map = $prop->getValue();
        $this->assertArrayHasKey('json', $map);
        $this->assertSame(Json::class, $map['json']);
    }

    public function test_json_field_can_be_instantiated(): void
    {
        $this->assertInstanceOf(\Dcat\Admin\Form\Field::class, new Json('specs'));
    }
}
