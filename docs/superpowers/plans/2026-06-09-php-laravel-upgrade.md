# dcat-admin PHP 8.2+ / Laravel 11+12 升级 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 把 `dcat/laravel-admin` 收敛到 PHP `^8.2` + Laravel `^11|^12`,建立 testbench 单测 + larastan baseline 质量门禁,并删除旧版兼容代码。

**Architecture:** 路线 A「安全网优先」。先用 orchestra/testbench 建一套不依赖宿主 app 的单元测试 + larastan baseline 作为回归网,再 bump composer 约束、升级依赖、删旧版兼容分支,全程靠测试 + larastan 验证。

**Tech Stack:** PHP 8.2, Laravel 11/12, PHPUnit 11, orchestra/testbench, larastan/larastan, doctrine/dbal 4。

> ⚠️ **本机所有 PHP 命令必须用 PHP 8.2**:默认 `php` 是 EOL 的 8.0。
> 约定别名(每个 Step 命令里已写全路径):
> `PHP82=/opt/homebrew/opt/php@8.2/bin/php`,`COMPOSER="$PHP82 /opt/homebrew/bin/composer"`。

---

## File Structure

新增:
- `tests/Testbench/TestCase.php` — testbench 单测基类(加载 `AdminServiceProvider`,不依赖宿主 app)。命名空间 `Dcat\Admin\Tests\Testbench`。
- `tests/Testbench/SmokeTest.php` — 冒烟单测(服务提供者 boot、核心类可用)。
- `phpunit.xml` — 单元测试配置(只跑 `tests/Testbench`,与 `phpunit.dusk.xml` 分离)。
- `phpstan.neon` — larastan 扩展 + level + paths。
- `phpstan-baseline.neon` — 冻结现有错误(自动生成)。

修改:
- `composer.json` — 依赖约束、require-dev、autoload-dev、scripts。
- `src/Repositories/EloquentRepository.php:327,875` — 删 `< 5.8.0` 死分支。
- `src/Support/Translator.php:113` — 删 `>= 6.0` 死分支。
- `src/Scaffold/ModelCreator.php:176` — 删 `>= 7.0.0` 死分支。
- `src/Console/ExportSeedCommand.php:35` — 删 `< 8.0.0` 死分支。

不动:`tests/` 下的 Dusk 测试体系、`phpunit.dusk.xml`、`src/Extend/VersionManager.php`(其 `version_compare` 是扩展版本比较,非 Laravel 版本)。

---

## 单元 1 — 测试骨架(Testbench)

### Task 1: 引入 orchestra/testbench 并建独立单测基类

**Files:**
- Modify: `composer.json`(require-dev 加 testbench;autoload-dev 加 Testbench 命名空间)
- Create: `tests/Testbench/TestCase.php`
- Create: `phpunit.xml`

- [ ] **Step 1: 加 testbench 到 require-dev**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.0/bin/php --version >/dev/null 2>&1; \
/opt/homebrew/opt/php@8.2/bin/php /opt/homebrew/bin/composer require --dev "orchestra/testbench:^9.0|^10.0" --no-interaction --no-update
```
Expected: composer.json 的 require-dev 出现 `orchestra/testbench`,不触发安装(--no-update)。

- [ ] **Step 2: 在 composer.json 注册 Testbench 测试命名空间**

修改 `composer.json` 的 `autoload-dev.psr-4`,从:
```json
"autoload-dev": {
    "psr-4": {
        "Dcat\\Admin\\Tests\\": "tests/"
    }
},
```
改为:
```json
"autoload-dev": {
    "psr-4": {
        "Dcat\\Admin\\Tests\\": "tests/"
    },
    "files": []
},
```
> 说明:`Dcat\Admin\Tests\` 已映射到 `tests/`,故 `tests/Testbench/` 自动对应 `Dcat\Admin\Tests\Testbench\`,无需新增映射。本步只确认映射存在(若已存在则不改)。

- [ ] **Step 3: 创建 testbench 单测基类**

Create `tests/Testbench/TestCase.php`:
```php
<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\AdminServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AdminServiceProvider::class,
        ];
    }
}
```

- [ ] **Step 4: 创建独立 phpunit.xml(只跑 tests/Testbench)**

Create `phpunit.xml`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Testbench">
            <directory>tests/Testbench</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

- [ ] **Step 5: 安装 testbench(用 PHP 8.2)**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php /opt/homebrew/bin/composer update orchestra/testbench --with-all-dependencies --no-interaction
```
Expected: testbench + 其依赖安装成功,无版本冲突。

- [ ] **Step 6: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add composer.json composer.lock phpunit.xml tests/Testbench/TestCase.php
git commit -m "test: 引入 orchestra/testbench 单测骨架"
```

### Task 2: 写第一个冒烟测试(服务提供者可 boot)

**Files:**
- Create: `tests/Testbench/SmokeTest.php`

- [ ] **Step 1: 写失败测试**

Create `tests/Testbench/SmokeTest.php`:
```php
<?php

namespace Dcat\Admin\Tests\Testbench;

use Dcat\Admin\AdminServiceProvider;
use Dcat\Admin\Color;

class SmokeTest extends TestCase
{
    public function test_admin_service_provider_is_loaded(): void
    {
        $this->assertArrayHasKey(
            AdminServiceProvider::class,
            $this->app->getLoadedProviders()
        );
    }

    public function test_color_value_object_resolves(): void
    {
        $color = new Color();

        $this->assertInstanceOf(Color::class, $color);
    }
}
```

- [ ] **Step 2: 跑测试,看是否通过/暴露真实问题**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
```
Expected: 两个测试通过。若 `AdminServiceProvider` boot 报错(缺 config/迁移),记录真实报错——这是升级要修的第一个运行期问题,转入「单元 5」流程修复后再回到此处绿灯。

- [ ] **Step 3: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add tests/Testbench/SmokeTest.php
git commit -m "test: 加 testbench 冒烟测试(服务提供者 boot + Color)"
```

### Task 3: 给 Grid / Form / Show 加实例化冒烟测试

**Files:**
- Create: `tests/Testbench/CoreComponentsTest.php`

- [ ] **Step 1: 写失败测试**

Create `tests/Testbench/CoreComponentsTest.php`:
```php
<?php

namespace Dcat\Admin\Tests\Testbench;

class CoreComponentsTest extends TestCase
{
    public function test_core_classes_exist(): void
    {
        $this->assertTrue(class_exists(\Dcat\Admin\Grid::class));
        $this->assertTrue(class_exists(\Dcat\Admin\Form::class));
        $this->assertTrue(class_exists(\Dcat\Admin\Show::class));
    }

    public function test_form_can_be_instantiated(): void
    {
        $form = new \Dcat\Admin\Form(new \Dcat\Admin\Tests\Testbench\Fixtures\NullRepository());

        $this->assertInstanceOf(\Dcat\Admin\Form::class, $form);
    }
}
```

- [ ] **Step 2: 创建测试夹具(空 Repository)**

Create `tests/Testbench/Fixtures/NullRepository.php`:
```php
<?php

namespace Dcat\Admin\Tests\Testbench\Fixtures;

use Dcat\Admin\Repositories\Repository;

class NullRepository extends Repository
{
}
```
> 若 `Dcat\Admin\Repositories\Repository` 为抽象且有必须实现的方法,改为继承 `Dcat\Admin\Repositories\EloquentRepository` 并在 Step 1 传入一个内存模型;执行时按真实签名调整。

- [ ] **Step 3: 跑测试**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
```
Expected: 全绿。`class_exists` 测试必过;`Form` 实例化若因依赖容器绑定失败,记录报错并在单元 5 修复。

- [ ] **Step 4: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add tests/Testbench/CoreComponentsTest.php tests/Testbench/Fixtures/NullRepository.php
git commit -m "test: 加 Grid/Form/Show 实例化冒烟测试"
```

---

## 单元 2 — 静态分析骨架(larastan)

### Task 4: 引入 larastan 并生成 baseline

**Files:**
- Modify: `composer.json`(require-dev 加 larastan;scripts.phpstan 指向 neon)
- Create: `phpstan.neon`
- Create: `phpstan-baseline.neon`(自动生成)

- [ ] **Step 1: 加 larastan**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php /opt/homebrew/bin/composer require --dev "larastan/larastan:^3.0" --no-interaction
```
Expected: larastan + 其要求的 phpstan 版本安装成功。

- [ ] **Step 2: 创建 phpstan.neon**

Create `phpstan.neon`:
```neon
includes:
    - vendor/larastan/larastan/extension.neon
    - phpstan-baseline.neon

parameters:
    paths:
        - src
    level: 5
    treatPhpDocTypesAsCertain: false
```

- [ ] **Step 3: 先建空 baseline 占位(避免 include 报错)**

Create `phpstan-baseline.neon`:
```neon
parameters:
    ignoreErrors: []
```

- [ ] **Step 4: 生成真实 baseline**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon --memory-limit=1G
```
Expected: 生成包含现有错误的 `phpstan-baseline.neon`,命令结束提示 baseline 已写入。

- [ ] **Step 5: 确认 baseline 下干净**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G
echo "EXIT: $?"
```
Expected: `[OK] No errors`,EXIT 0。记录 baseline 里的错误条目数(`grep -c "message:" phpstan-baseline.neon`)作为下轮清理目标。

- [ ] **Step 6: 更新 composer scripts.phpstan**

修改 `composer.json` scripts,从:
```json
"phpstan": "vendor/bin/phpstan analyse",
```
改为:
```json
"phpstan": "phpstan analyse --memory-limit=1G",
```
> phpstan 自动读取项目根 `phpstan.neon`。

- [ ] **Step 7: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add composer.json composer.lock phpstan.neon phpstan-baseline.neon
git commit -m "test: 引入 larastan + phpstan baseline 棘轮"
```

---

## 单元 3 — bump 约束 + 升级依赖

### Task 5: 收敛 composer.json 约束到 PHP 8.2 / Laravel 11+12

**Files:**
- Modify: `composer.json`(require)

- [ ] **Step 1: 改 require 约束**

修改 `composer.json` 的 `require`,从:
```json
"require": {
    "php": ">=7.1.0",
    "laravel/framework": "~5.5|~6.0|~7.0|~8.0|~9.0|~10.0|~11.0|~12.0",
    "spatie/eloquent-sortable": "3.*|4.*",
    "doctrine/dbal": "^2.6|^3.0|^4.0",
    "dcat/easy-excel": "*"
},
```
改为:
```json
"require": {
    "php": "^8.2",
    "laravel/framework": "^11.0|^12.0",
    "spatie/eloquent-sortable": "^4.0",
    "doctrine/dbal": "^4.0",
    "dcat/easy-excel": "*"
},
```

- [ ] **Step 2: 同步收敛 require-dev 约束**

修改 `composer.json` 的 `require-dev`,把 phpunit/dusk 旧下限去掉:
```json
"require-dev": {
    "laravel/dusk": "^8.0",
    "phpunit/phpunit": "^11.0",
    "fakerphp/faker": "^1.23",
    "mockery/mockery": "^1.6",
    "orchestra/testbench": "^9.0|^10.0",
    "larastan/larastan": "^3.0"
},
```

- [ ] **Step 3: 重新解析依赖**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php /opt/homebrew/bin/composer update --with-all-dependencies --no-interaction 2>&1 | tail -30
```
Expected: 干净解析。若 `dcat/easy-excel` 拉入 abandoned 的 box/spout 且报 PHP 8.2 不兼容,转 Task 6 处理后再回来。

- [ ] **Step 4: 跑单测 + larastan 确认没退化**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G; echo "PHPSTAN EXIT: $?"
```
Expected: 单测全绿;phpstan EXIT 0(相对 baseline 零新增)。

- [ ] **Step 5: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add composer.json composer.lock
git commit -m "chore: 收敛依赖约束到 PHP ^8.2 / Laravel ^11|^12"
```

### Task 6: 验证并处理 easy-excel / box/spout(abandoned)

**Files:**
- Modify: `composer.json`(必要时)

- [ ] **Step 1: 写 Excel 导出器加载冒烟测试**

Create `tests/Testbench/ExcelExporterTest.php`:
```php
<?php

namespace Dcat\Admin\Tests\Testbench;

class ExcelExporterTest extends TestCase
{
    public function test_excel_exporter_class_loads(): void
    {
        $this->assertTrue(class_exists(\Dcat\Admin\Grid\Exporters\ExcelExporter::class));
    }

    public function test_easy_excel_dependency_is_available(): void
    {
        $this->assertTrue(
            class_exists(\Dcat\EasyExcel\Excel::class),
            'dcat/easy-excel 未安装或不兼容当前 PHP'
        );
    }
}
```

- [ ] **Step 2: 跑测试**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --filter ExcelExporterTest --testdox
```
Expected: 通过 = easy-excel 在 PHP 8.2 可用,本 Task 完成。失败 = 进入 Step 3。

- [ ] **Step 3:(仅当 Step 2 失败)升级 easy-excel 约束**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php /opt/homebrew/bin/composer require "dcat/easy-excel:^1.2 || ^2.0" --no-interaction 2>&1 | tail -20
```
Expected: 拉到支持 PHP 8.2 / openspout 的新版;若上游无兼容版,记录为阻塞项升级到用户决策(是否替换导出实现)。重跑 Step 2 直到绿。

- [ ] **Step 4: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add composer.json composer.lock tests/Testbench/ExcelExporterTest.php
git commit -m "test: 验证 easy-excel 在 PHP 8.2 可用并固定兼容约束"
```

---

## 单元 4 — 删除旧版兼容代码

### Task 7: 删 EloquentRepository 的 < 5.8.0 死分支(2 处)

**Files:**
- Modify: `src/Repositories/EloquentRepository.php:327,875`

- [ ] **Step 1: 删第一处(约 line 327)**

把:
```php
$foreignKeyMethod = version_compare(app()->version(), '5.8.0', '<') ? 'getForeignKey' : 'getForeignKeyName';
```
改为:
```php
$foreignKeyMethod = 'getForeignKeyName';
```

- [ ] **Step 2: 删第二处(约 line 875)**

同样把第二处 `version_compare(app()->version(), '5.8.0', '<') ? 'getForeignKey' : 'getForeignKeyName'` 改为 `'getForeignKeyName'`。

- [ ] **Step 3: 验证**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php -l src/Repositories/EloquentRepository.php
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G; echo "PHPSTAN EXIT: $?"
```
Expected: 语法 OK;单测绿;phpstan EXIT 0。

- [ ] **Step 4: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Repositories/EloquentRepository.php
git commit -m "refactor: 删除 Laravel <5.8 的 getForeignKey 兼容分支"
```

### Task 8: 删 Translator / ModelCreator / ExportSeedCommand 的旧版分支

**Files:**
- Modify: `src/Support/Translator.php:113`
- Modify: `src/Scaffold/ModelCreator.php:176`
- Modify: `src/Console/ExportSeedCommand.php:35`

- [ ] **Step 1: Translator.php — 删 < 6.0 分支**

把:
```php
static::$method = version_compare(app()->version(), '6.0', '>=') ? 'get' : 'trans';
```
改为:
```php
static::$method = 'get';
```

- [ ] **Step 2: ExportSeedCommand.php — 删 < 8.0.0 分支**

把:
```php
$namespace = version_compare(app()->version(), '8.0.0', '<') ? 'seeds' : 'seeders';
```
改为:
```php
$namespace = 'seeders';
```

- [ ] **Step 3: ModelCreator.php — 化简 >= 7.0.0 恒真分支**

查看 `src/Scaffold/ModelCreator.php:176` 附近的 `if (version_compare(app()->version(), '7.0.0') >= 0) { ... }`,因最低 L11,该条件恒真:删掉 `if` 包裹,保留其内部分支代码;若有 `else`,删除 `else` 块。按实际代码结构调整缩进。

- [ ] **Step 4: 验证**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
for f in src/Support/Translator.php src/Scaffold/ModelCreator.php src/Console/ExportSeedCommand.php; do /opt/homebrew/opt/php@8.2/bin/php -l "$f"; done
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G; echo "PHPSTAN EXIT: $?"
```
Expected: 语法全 OK;单测绿;phpstan EXIT 0。

- [ ] **Step 5: Commit**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add src/Support/Translator.php src/Scaffold/ModelCreator.php src/Console/ExportSeedCommand.php
git commit -m "refactor: 删除 Laravel <8 的版本判断死分支"
```

### Task 9: 全仓复扫,确认无遗漏的 Laravel 版本分支

**Files:** 只读扫描,按发现再改

- [ ] **Step 1: 复扫**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
grep -rn "app()->version\|version_compare(app()" src || echo "CLEAN: 无残留 Laravel 版本分支"
```
Expected: 只剩 `src/Extend/VersionManager.php`(扩展版本比较,保留)。若有其他残留,按 Task 7/8 同法删除并各自 commit。

- [ ] **Step 2:(若有残留才需)逐处删除 + 验证 + commit**

对每处残留:确认是 Laravel 版本判断 → 取最低 L11 下的恒定分支 → 删另一支 → `php -l` + 单测 + phpstan → commit。

---

## 单元 5 — 修复 + baseline 棘轮(收尾)

### Task 10: 全量验证 + 记录基线收缩

**Files:** 只读 + 可能重生成 baseline

- [ ] **Step 1: 全 src 语法扫描**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
ERR=0; while IFS= read -r f; do /opt/homebrew/opt/php@8.2/bin/php -l "$f" >/dev/null 2>&1 || { ERR=$((ERR+1)); echo "FAIL: $f"; }; done < <(find src -name "*.php"); echo "语法错误: $ERR"
```
Expected: `语法错误: 0`。

- [ ] **Step 2: 单测 + phpstan 终检**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml --testdox
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G; echo "PHPSTAN EXIT: $?"
```
Expected: 单测全绿;phpstan EXIT 0。

- [ ] **Step 3: 收缩 baseline(删旧代码后部分错误应消失)**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --generate-baseline phpstan-baseline.neon --memory-limit=1G
grep -c "message:" phpstan-baseline.neon
```
Expected: baseline 条目数 ≤ 初始值;记录新数字到下方收尾备注。

- [ ] **Step 4: 跑一次 /health 对照基线**

Run:
```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
echo "用 /health skill 复测,对比 health-history.jsonl 里 2026-06-09 的首次基线"
```

- [ ] **Step 5: Commit baseline 收缩**

```bash
cd /Users/ma/devilbox/data/www/dcat-admin-update
git add phpstan-baseline.neon
git commit -m "chore: 删旧代码后收缩 phpstan baseline"
```

---

## 收尾备注(执行时填写)

- testbench 单测数:____ 个,全绿:☐
- phpstan baseline 初始条目:91(level 0 floor)/ 实际 level 5 初始:____ → 收尾:____
- easy-excel 在 PHP 8.2 是否需换实现:☐ 否 / ☐ 是(记录方案)
- 残留待办 → 下轮(新功能 / Dusk E2E 宿主 app / 清空 baseline)
