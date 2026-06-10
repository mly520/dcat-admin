# Grid 紧凑模式 + 表头吸顶 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 为 dcat-admin Grid 新增两个显式开关 `$grid->compact()`(高密度:小字号 + 小 padding)和 `$grid->stickyHeader()`(纵向滚动表头吸顶),默认关、向后兼容。

**Architecture:** 沿用 Grid 现有 `$options` 开关模式(蓝本 `withBorder()`/`scrollbar()`)。两开关设 `$this->options[key]`;`formatTableClass()` 按 option 追加语义 class;`render()` 内新增私有方法 `applyDensityStyles()` 按 option 调 `Admin::style()` 内联注入 CSS。复用现有 `fixColumns`(不动),不碰 SASS 构建。

**Tech Stack:** PHP 8.2, Laravel 11/12, dcat-admin, PHPUnit 11 + orchestra/testbench。

> ⚠️ 所有 PHP 命令用 `/opt/homebrew/opt/php@8.2/bin/php`(默认 php 是 EOL 的 8.0)。
> 测试:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml`
> 静态分析:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G`(须相对 baseline 零新增)

---

## File Structure

修改:
- `src/Grid.php` — 加 `compact()` / `stickyHeader()` 两个开关方法;`formatTableClass()` 内按 option 追加 class;新增私有 `applyDensityStyles()`;`render()` 内调用它。

新增:
- `tests/Testbench/GridDensityTest.php` — testbench 单测。

已确认的代码事实(实现时据此):
- `$options` 默认数组在 `src/Grid.php` ~line 154-174(含 `'bordered' => false`,`'table_class' => ['table','custom-data-table','data-table']`)。
- `formatTableClass()` ~line 455:`if ($this->options['bordered']) { $this->addTableClass([...]); } return implode(' ', array_unique((array) $this->options['table_class']));`
- `addTableClass($class)` ~line 448:merge 进 `$this->options['table_class']`。
- `render()` ~line 1005:依次 `callComposing()` / `build()` / `applyFixColumns()` / `setUpOptions()` / `addFilterScript()` / `addScript()` / `return $this->doWrap();`
- `Admin::style($style)` 是 `HasAssets` trait 的 static 方法;Grid.php 已用 `Admin::script(...)`(~line 1045),`Admin` 类可直接用(无需新 import)。

---

## Task 1: `compact()` 开关 + class 注入

**Files:**
- Modify: `src/Grid.php`
- Test: `tests/Testbench/GridDensityTest.php`

- [ ] **Step 1: 写失败测试**

Create `tests/Testbench/GridDensityTest.php`:
```php
<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\Grid;
use Dcat\Admin\Tests\Testbench\Fixtures\NullRepository;

class GridDensityTest extends TestCase
{
    protected function makeGrid(): Grid
    {
        return new Grid(new NullRepository());
    }

    public function test_compact_is_chainable(): void
    {
        $grid = $this->makeGrid();

        $this->assertSame($grid, $grid->compact());
    }

    public function test_compact_off_by_default(): void
    {
        $grid = $this->makeGrid();

        $this->assertStringNotContainsString('dcat-grid-compact', $grid->formatTableClass());
    }

    public function test_compact_adds_class_when_enabled(): void
    {
        $grid = $this->makeGrid();
        $grid->compact();

        $this->assertStringContainsString('dcat-grid-compact', $grid->formatTableClass());
    }
}
```
> 复用已存在的 `tests/Testbench/Fixtures/NullRepository.php`(json 功能那轮已建)。

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter GridDensityTest`
Expected: FAIL —— `Call to undefined method ... compact()`。

- [ ] **Step 3: 加 option 默认 + compact() 方法**

在 `src/Grid.php` 的 `$options` 默认数组里(`'bordered' => false,` 那一块)加两行:
```php
        'compact'             => false,
        'sticky_header'       => false,
```

在 `withBorder()` 方法附近(~line 644)加:
```php
    /**
     * 高密度紧凑模式(小字号 + 小内边距)。
     *
     * @param  bool  $value
     * @return $this
     */
    public function compact(bool $value = true)
    {
        $this->options['compact'] = $value;

        return $this;
    }

    /**
     * 表头吸顶(纵向滚动时固定表头)。
     *
     * @param  bool  $value
     * @return $this
     */
    public function stickyHeader(bool $value = true)
    {
        $this->options['sticky_header'] = $value;

        return $this;
    }
```

- [ ] **Step 4: 在 formatTableClass() 注入 class**

把 `formatTableClass()`(~line 455)改为:
```php
    public function formatTableClass()
    {
        if ($this->options['bordered']) {
            $this->addTableClass(['table-bordered', 'complex-headers', 'data-table']);
        }

        if ($this->options['compact']) {
            $this->addTableClass('dcat-grid-compact');
        }

        if ($this->options['sticky_header']) {
            $this->addTableClass('dcat-grid-sticky-header');
        }

        return implode(' ', array_unique((array) $this->options['table_class']));
    }
```
> 两个 class 都加在 `<table>` 上(经 `table_class`)。compact 作用于 td/th;sticky 作用于 thead th —— 都能用 `<table>` 上的 class 作后代选择器,无需改容器。

- [ ] **Step 5: 跑测试看通过(compact 部分)**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter GridDensityTest`
Expected: compact 的 3 个用例 PASS(sticky 测试还没加,本步只验证 compact)。

- [ ] **Step 6: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Grid.php tests/Testbench/GridDensityTest.php
git commit -m "feat: Grid compact() / stickyHeader() 开关 + class 注入"
```

## Task 2: stickyHeader class 测试

**Files:**
- Modify: `tests/Testbench/GridDensityTest.php`

- [ ] **Step 1: 加 sticky class 测试**

在 `GridDensityTest` 追加:
```php
    public function test_sticky_header_is_chainable(): void
    {
        $grid = $this->makeGrid();

        $this->assertSame($grid, $grid->stickyHeader());
    }

    public function test_sticky_header_off_by_default(): void
    {
        $grid = $this->makeGrid();

        $this->assertStringNotContainsString('dcat-grid-sticky-header', $grid->formatTableClass());
    }

    public function test_sticky_header_adds_class_when_enabled(): void
    {
        $grid = $this->makeGrid();
        $grid->stickyHeader();

        $this->assertStringContainsString('dcat-grid-sticky-header', $grid->formatTableClass());
    }
```

- [ ] **Step 2: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter GridDensityTest`
Expected: PASS(stickyHeader 方法+class 注入在 Task 1 已实现,本步补测试覆盖)。

- [ ] **Step 3: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add tests/Testbench/GridDensityTest.php
git commit -m "test: 补 stickyHeader class 注入测试"
```

## Task 3: applyDensityStyles() 注入 CSS

**Files:**
- Modify: `src/Grid.php`
- Test: `tests/Testbench/GridDensityTest.php`

- [ ] **Step 1: 写失败测试(渲染后 Admin 收集到 CSS)**

在 `GridDensityTest` 追加(顶部 use 加 `use Dcat\Admin\Admin;`):
```php
    public function test_compact_injects_css_on_render(): void
    {
        $grid = $this->makeGrid();
        $grid->compact();
        $grid->render();

        $this->assertStringContainsString('.dcat-grid-compact', Admin::asset()->cssToHtml());
    }

    public function test_sticky_injects_css_on_render(): void
    {
        $grid = $this->makeGrid();
        $grid->stickyHeader();
        $grid->render();

        $this->assertStringContainsString('position: sticky', Admin::asset()->cssToHtml());
    }

    public function test_no_density_css_when_disabled(): void
    {
        $grid = $this->makeGrid();
        $grid->render();

        $css = Admin::asset()->cssToHtml();
        $this->assertStringNotContainsString('.dcat-grid-compact', $css);
        $this->assertStringNotContainsString('.dcat-grid-sticky-header', $css);
    }
```
> `Admin::style()` 把内联样式收集进 asset 容器。读回的访问方式以实际 API 为准:实现时先确认 `Admin::asset()` 暴露的读取方法(可能是 `cssToHtml()` / `style()` getter / 其它)。**Step 1 跑失败时若是因为读取 API 名不对(而非功能没实现),据实改测试断言的读取调用**,保持"断言注入的 CSS 片段出现"这一意图不变。

- [ ] **Step 2: 跑测试看失败**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter GridDensityTest`
Expected: FAIL —— render 未注入任何 density CSS。

- [ ] **Step 3: 实现 applyDensityStyles() 并在 render 调用**

在 `src/Grid.php` 加私有方法(放在 `applyFixColumns()` 附近):
```php
    protected function applyDensityStyles()
    {
        if ($this->options['compact']) {
            \Dcat\Admin\Admin::style(
                <<<'CSS'
.dcat-grid-compact td, .dcat-grid-compact th { padding: .3rem .5rem; font-size: 12px; }
CSS
            );
        }

        if ($this->options['sticky_header']) {
            \Dcat\Admin\Admin::style(
                <<<'CSS'
.dcat-grid-sticky-header thead th { position: sticky; top: 0; z-index: 3; background: #fff; }
CSS
            );
        }
    }
```
在 `render()` 里 `$this->applyFixColumns();` 之后加一行:
```php
        $this->applyDensityStyles();
```

- [ ] **Step 4: 跑测试看通过**

Run: `/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter GridDensityTest`
Expected: PASS(全部 GridDensityTest 用例)。若读取 API 名调整过,确保意图断言仍成立。

- [ ] **Step 5: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Grid.php tests/Testbench/GridDensityTest.php
git commit -m "feat: render 时按开关内联注入紧凑/吸顶 CSS"
```

## Task 4: 全量验证 + 宿主 app 实测

**Files:** 只读验证 + 宿主 app 改动(`/Users/ma/devilbox/data/www/dcat-host`)

- [ ] **Step 1: 全套单测 + phpstan**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G; echo "PHPSTAN EXIT: $?"
```
Expected: 全绿(原有 + GridDensityTest);phpstan `[OK]` / EXIT 0(相对 baseline 零新增)。新代码报错则修到零新增,不扩大 baseline。

- [ ] **Step 2: 宿主 app 接线**

在 `/Users/ma/devilbox/data/www/dcat-host/app/Admin/Controllers/ShowcaseItemController.php` 的 `grid()` 里,`Grid::make(...)` 的闭包内首行(`$grid->column('id')...` 之前)加:
```php
            $grid->compact();
            $grid->stickyHeader();
```

- [ ] **Step 3: 浏览器实测**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-host
/opt/homebrew/opt/php@8.2/bin/php artisan config:clear >/dev/null 2>&1
echo "打开 http://127.0.0.1:8000/admin/showcase-items 看:字号是否变小、单元格更紧凑;纵向滚动时表头是否吸顶"
```
用浏览器(已登录 admin/admin)验证:列表字号变小、行更密;页面/容器纵向滚动时 thead 固定。检查无样式错乱、无控制台报错。

- [ ] **Step 4: 收尾**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
echo "宿主 app dcat-host 非 git 仓库,接线改动留工作区即可(实测用)"
```

---

## 收尾备注(执行时填写)

- 新增单测数:____;全绿:☐
- phpstan baseline:保持 ____ 条,零新增:☐
- 实测:紧凑字号生效 ☐ / 表头吸顶生效 ☐ / 与现有表格无冲突 ☐
- sticky z-index 是否需微调(与 fixColumns 叠加):____
