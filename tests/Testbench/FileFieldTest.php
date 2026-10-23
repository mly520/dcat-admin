<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Form\Field\File;

class FileFieldTest extends TestCase
{
    // 编辑表单未上传文件时,ConvertEmptyStringsToNull 会把字段变成 null;
    // 校验不应因 explode(null) 触发 PHP 8.1+ 弃用(每次保存带头像字段的表单都会走到)
    public function test_validator_accepts_null_value_without_deprecation(): void
    {
        $field = new File('avatar');

        $deprecations = $this->collectPhpDeprecations(function () use ($field) {
            $field->getValidator(['avatar' => null]);
        });

        $this->assertSame([], $deprecations);
    }
}
