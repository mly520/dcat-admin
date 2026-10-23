<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Form\Field\ListField;
use Illuminate\Support\MessageBag;

class ListFieldTest extends TestCase
{
    // 列表字段每一项的校验错误要汇总到字段名下,并且每条都是字符串:
    // 表单视图用 $errors->first() / get() 输出,嵌套数组会让 Blade 的 {{ }} 抛 TypeError
    public function test_validator_messages_are_flattened_to_strings(): void
    {
        $field = new ListField('items');

        $messages = $field->formatValidatorMessages(new MessageBag([
            'items.0' => ['第1项不能为空', '第1项格式错误'],
            'items.1' => ['第2项不能为空'],
        ]));

        $this->assertSame(['第1项不能为空', '第1项格式错误', '第2项不能为空'], $messages->get('items'));
        $this->assertSame('第1项不能为空', $messages->first('items'));
    }
}
