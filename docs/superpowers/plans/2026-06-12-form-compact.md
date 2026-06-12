# Form 紧凑/高密度模式 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 为 dcat-admin3 Form 新增 `$form->compact()` 紧凑/高密度模式(缩小字号 + 压缩间距),默认关、向后兼容。

**Architecture:** Form 是 Grid `compact()` 的同套路:`$form->compact()` 转发到 Builder 存标志;Builder 渲染时给 `<form>` 加 `dcat-form-compact` class 并经 `Admin::style()` 内联注入紧凑 CSS。复用现有排版(row/column/block/tab),不碰 SASS。

**Tech Stack:** PHP 8.2, Laravel 11/12, dcat-admin3(`Dcat\Admin3\`), PHPUnit 11 + orchestra/testbench。

> ⚠️ 所有 PHP 命令用 `/opt/homebrew/opt/php@8.2/bin/php`(默认 php 是 EOL 的 8.0)。
> 测试:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml`
> 静态分析:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G`(须相对 baseline 零新增)

---

## File Structure

修改:
- `src/Form/Builder.php` — 加 `$compact` 标志 + `compact()` setter + `isCompact()` getter;`render()` 内给 `<form>` class 加 `dcat-form-compact` + 注入 CSS。
- `src/Form.php` — 加 `compact()` 方法转发到 builder,链式返回 `$this`。

新增:
- `tests/Testbench/FormCompactTest.php` — testbench 单测。

已确认的代码事实(实现时据此):
- `Builder::render()`(~line 698):`$open = $this->open(['class' => 'form-horizontal']);`(~line 712)是 `<form>` 标签 class 来源;`render()` 最后 `return "{$open}{$content}{$this->close()}";`。
- `Builder::open($options)`:`$attributes['class'] = Arr::get($options, 'class');` —— 传进来的 class 直接用。
- Form 向 builder 转发模式:`$this->builder->xxx(...); return $this;`(如 `confirm()`,line 373)。
- `Admin::style($css)` 是 `HasAssets` trait static 方法;Builder 已 `use Dcat\Admin3\Admin`(line 6),可直接 `Admin::style(...)`。
- Admin 内联样式读回:`Admin::asset()->styleToHtml()`(Grid compact 那轮已确认的真实 API)。

---

## Task 1: Builder 加 compact 标志 + class 注入

**Files:**
- Modify: `src/Form/Builder.php`
- Modify: `src/Form.php`
- Test: `tests/Testbench/FormCompactTest.php`

- [ ] **Step 1: 写失败测试**

Create `tests/Testbench/FormCompactTest.php`:
```php
<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Form;
use Dcat\Admin3\Tests\Testbench\Fixtures\NullRepository;

class FormCompactTest extends TestCase
{
    protected function makeForm(): Form
    {
        return new Form(new NullRepository());
    }

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
}
```
> 复用已存在的 `tests/Testbench/Fixtures/NullRepository.php`。`Form` 构造接受 repository(与现有测试一致)。`$form->builder()` 取 Builder 实例(Form 有该 getter;若名不同,读 src/Form.php 确认真实 getter 名并调整)。

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter FormCompactTest`
Expected: FAIL —— `Call to undefined method ... compact()` / `isCompact()`。

- [ ] **Step 3: Builder 加标志 + setter/getter**

在 `src/Form/Builder.php` 类体内(属性区附近)加:
```php
    /**
     * @var bool
     */
    protected $compact = false;
```
并加方法(放在 render() 附近):
```php
    /**
     * 启用紧凑/高密度模式.
     *
     * @param  bool  $value
     * @return $this
     */
    public function compact(bool $value = true)
    {
        $this->compact = $value;

        return $this;
    }

    /**
     * @return bool
     */
    public function isCompact()
    {
        return $this->compact;
    }
```

- [ ] **Step 4: Form 加 compact() 转发**

在 `src/Form.php` 加(蓝本:`confirm()` 转发模式):
```php
    /**
     * 启用表单紧凑/高密度模式.
     *
     * @param  bool  $value
     * @return $this
     */
    public function compact(bool $value = true)
    {
        $this->builder->compact($value);

        return $this;
    }
```

- [ ] **Step 5: render() 给 form class 加 dcat-form-compact**

在 `src/Form/Builder.php` 的 `render()` 里,把:
```php
        $open = $this->open(['class' => 'form-horizontal']);
```
改为:
```php
        $formClass = $this->isCompact() ? 'form-horizontal dcat-form-compact' : 'form-horizontal';

        $open = $this->open(['class' => $formClass]);
```

- [ ] **Step 6: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter FormCompactTest`
Expected: PASS(3 个用例)。

- [ ] **Step 7: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Form/Builder.php src/Form.php tests/Testbench/FormCompactTest.php
git commit -m "feat: Form compact() 开关 + form class 注入"
```

## Task 2: 渲染时注入紧凑 CSS

**Files:**
- Modify: `src/Form/Builder.php`
- Test: `tests/Testbench/FormCompactTest.php`

- [ ] **Step 1: 写失败测试**

在 `FormCompactTest` 追加(顶部 use 加 `use Dcat\Admin3\Admin;`):
```php
    public function test_compact_injects_css_on_render(): void
    {
        $form = $this->makeForm();
        $form->compact();
        $form->builder()->render();

        $this->assertStringContainsString('.dcat-form-compact', Admin::asset()->styleToHtml());
    }

    public function test_no_css_when_not_compact(): void
    {
        $form = $this->makeForm();
        $form->builder()->render();

        $this->assertStringNotContainsString('.dcat-form-compact', Admin::asset()->styleToHtml());
    }
```
> 若 `$form->builder()->render()` 在 testbench 下因缺数据/视图依赖抛错,改为渲染最小可用表单(构造 Form 后加一个 text 字段再 render);目标是触发注入路径。实现时据实调整,保持"compact 时 styleToHtml 含 `.dcat-form-compact`、非 compact 时不含"两个断言不变。**注意测试间 Admin asset 是全局静态**——在 `setUp()` 里重置 `Admin::asset()->style = []` 防跨用例污染(参考 GridDensityTest 的做法)。

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter FormCompactTest`
Expected: FAIL —— render 未注入 compact CSS。

- [ ] **Step 3: 加 applyCompactStyle() 并在 render 调用**

在 `src/Form/Builder.php` 加私有方法:
```php
    protected function applyCompactStyle()
    {
        if (! $this->isCompact()) {
            return;
        }

        Admin::style(
            <<<'CSS'
.dcat-form-compact .form-group { margin-bottom: .5rem; }
.dcat-form-compact .form-control, .dcat-form-compact label, .dcat-form-compact .form-label { font-size: 12px; }
.dcat-form-compact .form-control { padding: .25rem .5rem; height: auto; min-height: calc(1.5em + .5rem); }
.dcat-form-compact textarea.form-control { min-height: 60px; }
.dcat-form-compact .help-block { font-size: 11px; margin-top: 2px; }
.dcat-form-compact .control-label, .dcat-form-compact .col-form-label { padding-top: .25rem; padding-bottom: .25rem; }
CSS
        );
    }
```
在 `render()` 方法开头(`$this->removeIgnoreFields();` 之后)加一行:
```php
        $this->applyCompactStyle();
```

- [ ] **Step 4: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter FormCompactTest`
Expected: PASS(全部 FormCompactTest 用例)。

- [ ] **Step 5: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Form/Builder.php tests/Testbench/FormCompactTest.php
git commit -m "feat: Form compact 渲染时内联注入紧凑 CSS"
```

## Task 3: 全量验证 + 宿主 app 实测

**Files:** 只读验证 + 宿主 app 改动(`/Users/ma/devilbox/data/www/dcat-host`)

- [ ] **Step 1: 全套单测 + phpstan**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G; echo "PHPSTAN EXIT: $?"
```
Expected: 全绿(原有 + FormCompactTest);phpstan `[OK]` / EXIT 0(相对 baseline 零新增)。新代码报错则修到零新增,不扩大 baseline。

- [ ] **Step 2: 宿主 app 接线**

在 `/Users/ma/devilbox/data/www/dcat-host/app/Admin/Controllers/ShowcaseItemController.php` 的 `form()` 里,`Form::make(...)` 的闭包内首行(第一个 `$form->tab(...)` 之前)加:
```php
            $form->compact();
```

- [ ] **Step 3: 浏览器实测**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-host
/opt/homebrew/opt/php@8.2/bin/php artisan config:clear >/dev/null 2>&1
echo "打开 http://127.0.0.1:8000/admin/showcase-items/1/edit 看表单字号/间距是否变紧凑(对照之前的预览)"
```
用浏览器(已登录 admin/admin)验证:编辑表单字号变小、行距压缩、可读;`<form>` 含 `dcat-form-compact` class;无样式错乱、无控制台报错。

- [ ] **Step 4: 收尾**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
echo "宿主 app dcat-host 非 git 仓库,接线改动留工作区即可(实测用)"
```

---

## 收尾备注(执行时填写)

- 新增单测数:____;全绿:☐
- phpstan baseline:保持 ____ 条,零新增:☐
- 实测:字号变小 ☐ / 间距压缩 ☐ / 可读 ☐ / 与现有 tab 排版无冲突 ☐
