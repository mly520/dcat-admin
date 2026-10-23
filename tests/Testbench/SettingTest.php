<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Support\Setting;

class SettingTest extends TestCase
{
    // 不传 $k 时应追加到列表末尾;旧实现条件写反,会落到 $results[null](PHP 8.5 弃用,且值被存到 '' 键)
    public function test_add_without_key_appends_to_list(): void
    {
        $setting = new Setting(['ids' => ['a']]);

        $setting->add('ids', 'b');

        $this->assertSame(['a', 'b'], $setting->getArray('ids'));
    }

    // 传 $k 时应按键写入,而不是追加
    public function test_add_with_key_sets_keyed_value(): void
    {
        $setting = new Setting(['map' => ['x' => 1]]);

        $setting->add('map', 2, 'y');

        $this->assertSame(['x' => 1, 'y' => 2], $setting->getArray('map'));
    }
}
