# Form `json` 字段 + Grid `->json()` 显示器 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 为 dcat-admin 框架新增一个 Form `json` 字段(textarea + 客户端校验/格式化)和一个 Grid `->json()` 列显示器(pretty `<pre>` 展示)。

**Architecture:** Form 字段 `Json` 以 `KeyValue` 为蓝本继承 `Form\Field`,提交 `json_decode` 成数组(列需 array cast)、非法 JSON 由 `getValidator` 服务端拦截、空→null;视图是一个 textarea + 内联 JS 格式化按钮。Grid `Json` 显示器继承 `AbstractDisplayer`,`display()` 输出转义后的 pretty JSON `<pre>`。两者各在 `Form::$availableFields` 和 `Column::$displayers` 注册一行。

**Tech Stack:** PHP 8.2, Laravel 11/12, dcat-admin, PHPUnit 11 + orchestra/testbench。

> ⚠️ 所有 PHP 命令用 `/opt/homebrew/opt/php@8.2/bin/php`(默认 php 是 EOL 的 8.0)。
> 测试入口:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml`
> 静态分析:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G`(须相对 baseline 零新增)

---

## File Structure

新增:
- `src/Form/Field/Json.php` — Json 表单字段(prepareInputValue / render / getValidator)。
- `resources/views/form/json.blade.php` — 字段视图(textarea + 格式化按钮 + error/help-block)。
- `src/Grid/Displayers/Json.php` — Grid 列显示器,`display()` 返回 pretty `<pre>`。
- `tests/Testbench/JsonFieldTest.php`、`tests/Testbench/JsonDisplayerTest.php` — testbench 单测。

修改:
- `src/Form.php` — `$availableFields` 注册 `'json'` + 顶部 `@method` 注解。
- `src/Grid/Column.php` — `$displayers` 注册 `'json'` + 顶部 `@method` 注解。

---

## Task 1: Json 字段类骨架 + 注册

**Files:**
- Create: `src/Form/Field/Json.php`
- Modify: `src/Form.php`
- Test: `tests/Testbench/JsonFieldTest.php`

- [ ] **Step 1: 写失败测试**

Create `tests/Testbench/JsonFieldTest.php`:
```php
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
        $field = new Json('specs');

        $this->assertInstanceOf(\Dcat\Admin\Form\Field::class, $field);
    }
}
```

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonFieldTest`
Expected: FAIL —— `Class "Dcat\Admin\Form\Field\Json" not found` / 'json' 不在 map。

- [ ] **Step 3: 创建字段类骨架**

Create `src/Form/Field/Json.php`:
```php
<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Illuminate\Support\Arr;

class Json extends Field
{
    //
}
```

- [ ] **Step 4: 在 Form.php 注册**

在 `src/Form.php` 的 `$availableFields` 数组里(`'keyValue' => Field\KeyValue::class,` 附近)加一行:
```php
        'json'                => Field\Json::class,
```
并在 `src/Form.php` 类顶部的 `@method` 注解块里(`@method Field\KeyValue keyValue(...)` 附近)加:
```php
 * @method Field\Json              json($column, $label = '')
```

- [ ] **Step 5: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonFieldTest`
Expected: PASS (2 个用例)。

- [ ] **Step 6: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Form/Field/Json.php src/Form.php tests/Testbench/JsonFieldTest.php
git commit -m "feat: 新增 Form json 字段骨架并注册"
```

## Task 2: prepareInputValue(提交解码成数组,空→null)

**Files:**
- Modify: `src/Form/Field/Json.php`
- Test: `tests/Testbench/JsonFieldTest.php`

- [ ] **Step 1: 加失败测试**

在 `JsonFieldTest` 类里追加:
```php
    public function test_prepare_decodes_json_string_to_array(): void
    {
        $field = new Json('specs');

        $this->assertSame(['a' => 1, 'b' => [2, 3]], $field->prepare('{"a":1,"b":[2,3]}'));
    }

    public function test_prepare_empty_returns_null(): void
    {
        $field = new Json('specs');

        $this->assertNull($field->prepare(''));
        $this->assertNull($field->prepare('   '));
    }

    public function test_prepare_passes_through_array(): void
    {
        $field = new Json('specs');

        $this->assertSame(['x' => 1], $field->prepare(['x' => 1]));
    }
```

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonFieldTest`
Expected: FAIL —— `prepare('{"a":1...}')` 默认原样返回字符串,断言不等。

- [ ] **Step 3: 实现 prepareInputValue**

在 `src/Form/Field/Json.php` 类体内加:
```php
    protected function prepareInputValue($value)
    {
        if ($value === null || is_array($value)) {
            return $value;
        }

        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        $decoded = json_decode($trimmed, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
```

- [ ] **Step 4: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonFieldTest`
Expected: PASS。

- [ ] **Step 5: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Form/Field/Json.php tests/Testbench/JsonFieldTest.php
git commit -m "feat: json 字段提交时解码成数组(空→null)"
```

## Task 3: getValidator(非法 JSON 服务端拦截)

**Files:**
- Modify: `src/Form/Field/Json.php`
- Test: `tests/Testbench/JsonFieldTest.php`

- [ ] **Step 1: 加失败测试**

在 `JsonFieldTest` 追加:
```php
    public function test_validator_fails_on_invalid_json(): void
    {
        $field = new Json('specs');

        $validator = $field->getValidator(['specs' => '{invalid json']);

        $this->assertNotFalse($validator);
        $this->assertTrue($validator->fails());
    }

    public function test_validator_passes_on_valid_json(): void
    {
        $field = new Json('specs');

        $validator = $field->getValidator(['specs' => '{"a":1}']);

        $this->assertNotFalse($validator);
        $this->assertFalse($validator->fails());
    }

    public function test_validator_passes_on_empty(): void
    {
        $field = new Json('specs');

        $validator = $field->getValidator(['specs' => '']);

        // 空值不应判为非法 JSON(若返回 false 表示跳过校验亦可接受)
        $this->assertTrue($validator === false || ! $validator->fails());
    }
```

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonFieldTest`
Expected: FAIL —— 基类 `getValidator` 不针对 JSON 合法性,`fails()` 为 false。

- [ ] **Step 3: 实现 getValidator**

在 `src/Form/Field/Json.php` 类体内加:
```php
    public function getValidator(array $input)
    {
        if ($this->validator) {
            return $this->validator->call($this, $input);
        }

        if (! is_string($this->column) || ! Arr::has($input, $this->column)) {
            return false;
        }

        $value = Arr::get($input, $this->column);

        $rules = [
            $this->column => function ($attribute, $val, $fail) {
                if ($val === null || $val === '' || (is_string($val) && trim($val) === '')) {
                    return;
                }
                if (! is_string($val)) {
                    return;
                }
                json_decode($val);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail(admin_trans('admin.json_invalid') ?: 'The :attribute must be valid JSON.');
                }
            },
        ];

        return validator(
            [$this->column => $value],
            $rules,
            $this->getValidationMessages(),
            [$this->column => $this->label]
        );
    }
```
> 说明:`admin_trans('admin.json_invalid')` 若无翻译键会回退到默认英文串(`?:`)。保持与框架其它字段同风格,不强制新增语言键。

- [ ] **Step 4: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonFieldTest`
Expected: PASS。

- [ ] **Step 5: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Form/Field/Json.php tests/Testbench/JsonFieldTest.php
git commit -m "feat: json 字段服务端校验拦截非法 JSON"
```

## Task 4: render() + 视图(textarea 显示 pretty JSON)

**Files:**
- Modify: `src/Form/Field/Json.php`
- Create: `resources/views/form/json.blade.php`
- Test: `tests/Testbench/JsonFieldTest.php`

- [ ] **Step 1: 加失败测试**

在 `JsonFieldTest` 追加:
```php
    public function test_render_outputs_textarea_with_pretty_json(): void
    {
        $field = new Json('specs');
        $field->default(['a' => 1]);

        $html = (string) $field->render();

        $this->assertStringContainsString('<textarea', $html);
        // pretty_print 会把键值缩进展开
        $this->assertStringContainsString('"a": 1', $html);
    }
```

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter test_render_outputs_textarea`
Expected: FAIL —— 视图 `admin::form.json` 不存在 / 无 `<textarea>`。

- [ ] **Step 3: 实现 render()**

在 `src/Form/Field/Json.php` 类体内加:
```php
    public function render()
    {
        $value = $this->value();

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = $decoded;
            }
        }

        $formatted = '';
        if (is_array($value)) {
            $formatted = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } elseif (is_string($value)) {
            $formatted = $value;
        }

        $this->addVariables(['formatted' => $formatted]);

        return parent::render();
    }
```

- [ ] **Step 4: 创建视图**

Create `resources/views/form/json.blade.php`:
```blade
<div class="{{$viewClass['form-group']}}">

    <div class="{{ $viewClass['label'] }} control-label">
        <span>{!! $label !!}</span>
    </div>

    <div class="{{$viewClass['field']}}">

        @include('admin::form.error')

        <textarea
            name="{{$name}}"
            class="form-control dcat-json-field {{$class}}"
            rows="8"
            style="font-family: monospace;"
            {!! $attributes !!}
        >{{ $formatted }}</textarea>

        <button type="button" class="btn btn-xs btn-default mt-1 dcat-json-format" data-target="{{$name}}">
            {{ admin_trans('admin.format') ?: 'Format / Validate' }}
        </button>
        <span class="text-danger dcat-json-error" data-target="{{$name}}" style="display:none;margin-left:6px;">
            JSON 格式错误
        </span>

        @include('admin::form.help-block')

    </div>
</div>

<script once>
$(document).off('click', '.dcat-json-format').on('click', '.dcat-json-format', function () {
    var name = $(this).data('target');
    var $ta = $('textarea[name="' + name + '"]');
    var $err = $('.dcat-json-error[data-target="' + name + '"]');
    try {
        var v = $ta.val();
        var obj = (v && v.trim() !== '') ? JSON.parse(v) : null;
        $ta.val(obj === null ? '' : JSON.stringify(obj, null, 2));
        $ta.css('border-color', '');
        $err.hide();
    } catch (e) {
        $ta.css('border-color', 'red');
        $err.show();
    }
});
</script>
```
> 视图变量(`$viewClass`、`$label`、`$name`、`$class`、`$attributes`)与 `resources/views/form/select.blade.php` 一致;若 `$attributes` 等变量名有出入,以 `Field::defaultVariables()` 实际提供的为准(Step 5 的渲染测试会暴露)。`<script once>` 是 dcat 的脚本去重指令(见其它字段视图)。

- [ ] **Step 5: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonFieldTest`
Expected: PASS(全部 JsonFieldTest 用例)。

- [ ] **Step 6: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Form/Field/Json.php resources/views/form/json.blade.php tests/Testbench/JsonFieldTest.php
git commit -m "feat: json 字段视图(textarea + 格式化按钮)与 pretty 回显"
```

## Task 5: Grid `->json()` 列显示器

**Files:**
- Create: `src/Grid/Displayers/Json.php`
- Modify: `src/Grid/Column.php`
- Test: `tests/Testbench/JsonDisplayerTest.php`

- [ ] **Step 1: 写失败测试**

Create `tests/Testbench/JsonDisplayerTest.php`:
```php
<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\Grid\Column;
use ReflectionClass;

class JsonDisplayerTest extends TestCase
{
    public function test_json_displayer_is_registered(): void
    {
        $ref = new ReflectionClass(Column::class);
        $prop = $ref->getProperty('displayers');
        $prop->setAccessible(true);
        $map = $prop->getValue();

        $this->assertArrayHasKey('json', $map);
        $this->assertSame(\Dcat\Admin\Grid\Displayers\Json::class, $map['json']);
    }

    public function test_displayer_renders_pretty_pre_for_array(): void
    {
        $displayer = new \Dcat\Admin\Grid\Displayers\Json(['a' => 1], $this->fakeGrid(), $this->fakeColumn(), (object) []);

        $html = $displayer->display();

        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('"a": 1', $html);
    }

    public function test_displayer_safe_on_null_and_invalid(): void
    {
        $g = $this->fakeGrid();
        $c = $this->fakeColumn();

        $this->assertSame('', (new \Dcat\Admin\Grid\Displayers\Json(null, $g, $c, (object) []))->display());

        $invalid = (new \Dcat\Admin\Grid\Displayers\Json('{not json', $g, $c, (object) []))->display();
        $this->assertStringContainsString('{not json', $invalid);
    }

    protected function fakeGrid()
    {
        return new \Dcat\Admin\Grid(new \Illuminate\Database\Eloquent\Collection());
    }

    protected function fakeColumn()
    {
        return new Column('specs', '规格');
    }
}
```
> 若 `new Grid(...)` / `new Column(...)` 构造在 testbench 下需要更多依赖,改用一个最小桩:`$this->getMockBuilder(...)`。目标是能 new 出 displayer 并调用 `display()`;构造参数顺序见 `AbstractDisplayer::__construct($value, Grid $grid, Column $column, $row)`。

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonDisplayerTest`
Expected: FAIL —— `Displayers\Json` 不存在 / 'json' 未注册。

- [ ] **Step 3: 创建显示器类**

Create `src/Grid/Displayers/Json.php`:
```php
<?php

namespace Dcat\Admin\Grid\Displayers;

class Json extends AbstractDisplayer
{
    public function display()
    {
        $value = $this->value;

        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // 非法 JSON 字符串:原样转义显示,安全降级
                return e($value);
            }
            $value = $decoded;
        }

        $formatted = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return '<pre class="dcat-json-display" style="margin:0;white-space:pre-wrap;word-break:break-all;">'.e($formatted).'</pre>';
    }
}
```

- [ ] **Step 4: 在 Column.php 注册**

在 `src/Grid/Column.php` 的 `$displayers` 数组里(`'copyable' => Displayers\Copyable::class,` 附近)加:
```php
        'json'             => Displayers\Json::class,
```
并在 `src/Grid/Column.php` 顶部 `@method` 注解块里(`@method $this copyable()` 附近)加:
```php
 * @method $this json()
```

- [ ] **Step 5: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter JsonDisplayerTest`
Expected: PASS。

- [ ] **Step 6: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Grid/Displayers/Json.php src/Grid/Column.php tests/Testbench/JsonDisplayerTest.php
git commit -m "feat: 新增 Grid ->json() 列显示器"
```

## Task 6: 全量验证 + 宿主 app 实测

**Files:** 只读验证 + 宿主 app 改动(`/Users/ma/devilbox/data/www/dcat-host`)

- [ ] **Step 1: 全套单测 + phpstan**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G; echo "PHPSTAN EXIT: $?"
```
Expected: 单测全绿(原有 + 新增 JsonField/JsonDisplayer);phpstan `[OK]` / EXIT 0(相对 baseline 零新增)。若 phpstan 报新错误且确属新代码,修到零新增(不得扩大 baseline 掩盖)。

- [ ] **Step 2: 宿主 app 接线(把 specs 换成 json 字段 + grid ->json())**

在 `/Users/ma/devilbox/data/www/dcat-host/app/Admin/Controllers/ShowcaseItemController.php`:
- form() 里把 `$form->keyValue('specs', '规格参数');` 改为 `$form->json('specs', '规格参数(JSON)');`
- grid() 里把 `$grid->column('tags', '标签')->label('primary');` 下方合适位置加一列(或改 specs 展示):`$grid->column('specs', '规格')->json();`

- [ ] **Step 3: 浏览器实测**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-host
/opt/homebrew/opt/php@8.2/bin/php artisan config:clear >/dev/null 2>&1
echo "打开 http://127.0.0.1:8000/admin/showcase-items/1/edit 看 specs 是否为 pretty JSON 的 textarea + 格式化按钮"
echo "打开 http://127.0.0.1:8000/admin/showcase-items 看 specs 列是否 pretty <pre> 展示"
```
用浏览器(已登录 admin/admin)验证:编辑页 specs 是 JSON textarea,点「格式化」能重排;改非法 JSON 提交被拦;列表页 specs 列 pretty 展示。

- [ ] **Step 4: Commit(若改了宿主 app)**

```bash
cd /Users/ma/devilbox/data/www/dcat-host
git add app/Admin/Controllers/ShowcaseItemController.php 2>/dev/null && git commit -m "test: 用 json 字段/显示器接线 showcase 实测" || echo "dcat-host 非 git 或无改动,跳过"
```

---

## 收尾备注(执行时填写)

- 新增单测数:____;全绿:☐
- phpstan baseline:保持 ____ 条,零新增:☐
- 宿主 app 实测:编辑回显 ☐ / 格式化按钮 ☐ / 非法拦截 ☐ / 列表展示 ☐
