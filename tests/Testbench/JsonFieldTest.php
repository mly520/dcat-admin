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

    public function test_prepare_decodes_json_string(): void
    {
        $field = new Json('specs');
        $result = $field->prepare('{"a":1,"b":[2,3]}');
        $this->assertSame(['a' => 1, 'b' => [2, 3]], $result);
    }

    public function test_prepare_empty_string_returns_null(): void
    {
        $field = new Json('specs');
        $this->assertNull($field->prepare(''));
    }

    public function test_prepare_whitespace_string_returns_null(): void
    {
        $field = new Json('specs');
        $this->assertNull($field->prepare('   '));
    }

    public function test_prepare_passthrough_array(): void
    {
        $field = new Json('specs');
        $this->assertSame(['x' => 1], $field->prepare(['x' => 1]));
    }

    public function test_getValidator_fails_on_invalid_json(): void
    {
        $field = new Json('specs');
        $validator = $field->getValidator(['specs' => '{invalid json']);
        $this->assertNotFalse($validator);
        $this->assertTrue($validator->fails());
    }

    public function test_getValidator_passes_on_valid_json(): void
    {
        $field = new Json('specs');
        $validator = $field->getValidator(['specs' => '{"a":1}']);
        $this->assertNotFalse($validator);
        $this->assertFalse($validator->fails());
    }

    public function test_getValidator_skips_empty_string(): void
    {
        $field = new Json('specs');
        $result = $field->getValidator(['specs' => '']);
        // Either returns false or a passing validator
        if ($result !== false) {
            $this->assertFalse($result->fails());
        } else {
            $this->assertFalse($result);
        }
    }
}
