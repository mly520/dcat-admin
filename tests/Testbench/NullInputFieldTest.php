<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Form\Field\Select;
use Dcat\Admin3\Form\Field\Tags;

/**
 * ConvertEmptyStringsToNull 中间件会把空字符串转成 null,
 * 字段在处理 null 元素时不应触发 PHP 8.1+ "Passing null to parameter" 弃用,且空值仍需被过滤掉。
 */
class NullInputFieldTest extends TestCase
{
    // 标签输入框留空提交时,空项要被丢弃,只保存有效标签
    public function test_tags_drop_null_and_empty_items_without_deprecation(): void
    {
        $field = new Tags('tags');

        $result = null;
        $deprecations = $this->collectPhpDeprecations(function () use ($field, &$result) {
            $result = $field->prepare(['重要', null, '', '0', '技術']);
        });

        $this->assertSame([], $deprecations);
        // '0' 是合法标签,不能被当成空值丢掉
        $this->assertSame([0 => '重要', 3 => '0', 4 => '技術'], $result);
    }

    // replaceOptions() 原样赋值不经过滤,来自 pluck() 的 null 标签会进入 formatOptions()(每次渲染都会执行)
    public function test_select_replaced_options_drop_null_labels_without_deprecation(): void
    {
        $field = new Select('user_id');
        $field->replaceOptions([1 => '山田', 2 => null, 3 => '', 4 => '0']);

        $deprecations = $this->collectPhpDeprecations(function () use ($field) {
            (fn () => $this->formatOptions())->call($field);
        });

        $this->assertSame([], $deprecations);
        $this->assertSame([1 => '山田', 4 => '0'], (fn () => $this->options)->call($field));
    }
}
