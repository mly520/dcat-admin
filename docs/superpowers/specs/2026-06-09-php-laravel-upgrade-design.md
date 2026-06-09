# dcat-admin 升级到 PHP 8.2+ / Laravel 11+12 — 设计文档

- 日期:2026-06-09
- 分支:`upgrade/php-laravel`
- 包:`dcat/laravel-admin`(`type: library`)
- 状态:已确认,待写实现计划

## 背景

本仓库是 dcat-admin 框架本体。现状 `composer.json` 声明 `php >=7.1.0`、`laravel/framework ~5.5|~6|~7|~8|~9|~10|~11|~12`,下限过老,背着大量旧版本兼容代码。基线体检(`/health`,PHP 8.2)结果:

- 442 个源文件在 PHP 8.2 下 **零语法错误**。
- phpstan **level 0 = 91 个错误**(`new static()` 不安全用法、缺失返回值等),仅为地板线。
- **没有可独立运行的测试**:`tests/` 全是 Laravel Dusk 浏览器测试,需先建宿主 Laravel app 才能跑。
- `dcat/easy-excel` 依赖的 `box/spout` 已被标记 **abandoned**。

定位决策:**自己用 / 内部依赖**(非对外发布),因此可激进砍掉旧版本支持。

## 目标与范围

**目标**:把 `dcat/laravel-admin` 收敛到 **PHP `^8.2` + Laravel `^11|^12`**,确保代码在新栈下加载/运行正常,并建立可持续的测试与静态分析质量门禁。

**本轮范围内:**
- 收敛 composer 依赖约束到目标矩阵。
- 引入 `orchestra/testbench` + 核心模块单元测试作为回归网。
- 引入 `larastan` + phpstan baseline 棘轮。
- 删除 PHP < 8.2 / Laravel < 11 的旧版兼容代码。
- 处理 abandoned 的 box/spout 依赖链。

**本轮明确不做(YAGNI):**
- 新功能(下一轮单独 brainstorming + spec)。
- 一次性清空 91 个 phpstan 错误(改用 baseline 棘轮,逐步清)。
- 支持 Laravel < 11 / PHP < 8.2。
- 修复 Dusk 浏览器 E2E(以 testbench 单测为主网,Dusk 延后)。

## 目标依赖矩阵(composer.json)

`require`:
- `php: ^8.2`
- `laravel/framework: ^11.0|^12.0`(删除 `~5.5|~6|~7|~8|~9|~10`)
- `doctrine/dbal: ^4`
- `spatie/eloquent-sortable: ^4`
- `dcat/easy-excel`:验证在 PHP 8.2 + box/spout abandoned 下是否可用;必要时升级到新版或迁移到 openspout 系。

`require-dev`:
- `phpunit/phpunit: ^11`
- `orchestra/testbench`(L11 → `^9`,L12 → `^10`;按矩阵取并集)
- `larastan/larastan`(对应 L11/12 的版本)
- `laravel/dusk`:当前可用版(Dusk 本轮延后,但保留依赖)
- `fakerphp/faker`、`mockery/mockery`:维持

## 执行路线(A:安全网优先)

按以下单元顺序推进,每单元产出可独立验证:

### 单元 1 — 测试骨架(Testbench)
- 引入 `orchestra/testbench`。
- 新建独立 `phpunit.xml`(单元/功能测试),与现有 `phpunit.dusk.xml`(浏览器 E2E)分离。
- 让测试基类基于 testbench `TestCase`(加载 `AdminServiceProvider`),无需完整宿主 app。
- 给核心模块写冒烟单测:`Grid`、`Form`、`Show` 的实例化 + 基础渲染路径;`Admin` / `AdminServiceProvider` 能在 testbench 下 boot。
- 验证:`vendor/bin/phpunit -c phpunit.xml` 全绿。

### 单元 2 — 静态分析骨架(larastan)
- 引入 `larastan/larastan`。
- 新建 `phpstan.neon`:挂 larastan 扩展,`paths: [src]`,设定起始 level(建议 level 5,按实际可达性调整)。
- `phpstan analyse --generate-baseline` 冻结现有错误到 `phpstan-baseline.neon`。
- `composer phpstan` 脚本接好(指向新 neon)。
- 验证:`composer phpstan` 在 baseline 下退出码 0。

### 单元 3 — bump 约束 + 升级依赖
- 按目标矩阵改 `composer.json`。
- 解决 box/spout / easy-excel 兼容(优先验证 easy-excel 在 8.2 的导入/导出实际可用性)。
- 重新 `composer update`,生成新 lock。
- 验证:`composer install` 干净;单元 1 测试仍绿。

### 单元 4 — 删除旧版兼容代码
- 全仓搜 `version_compare`、Laravel 版本判断(如 `Admin::longVersion`、`app()->version()` 分支)、PHP 版本分支、`@php` 约束相关 polyfill。
- 删除针对 PHP < 8.2 / Laravel < 11 的死路径。
- 遵循 CLAUDE.md "代码复用纪律":动手前先确认是否有现成实现/分支再删。
- 验证:`php -l` 全 src 干净;单元 1 测试绿;larastan 零新增。

### 单元 5 — 修复 + baseline 棘轮
- 跑测试 + larastan,修复新栈下暴露的运行期/静态问题。
- 删旧代码后重新生成/收缩 baseline(死代码删除应让 baseline 自然变小)。
- 本轮不要求清空 baseline,但记录剩余条目数作为下轮目标。

## 验证方式(整体)

- `vendor/bin/phpunit -c phpunit.xml`(testbench 单测)全绿。
- `composer phpstan`:相对 baseline **零新增错误**。
- `php -l` 扫描全 `src/` 零语法错误。
- composer 依赖在 PHP 8.2 下干净解析、安装。
- (可选,延后)升级 chromedriver + 建 L12 宿主 app 跑 Dusk E2E。

> 注意:所有 PHP 命令需用 `/opt/homebrew/opt/php@8.2/bin/php`(本机默认 `php` 为 EOL 的 8.0)。

## 主要风险

1. **box/spout abandoned**:`dcat/easy-excel` 在 PHP 8.2 下可能导入/导出失败 —— 单元 3 优先验证,必要时换依赖。
2. **Octane 集成 / 第三方包**:部分集成或依赖对 L11/12 的兼容性未知,可能需逐个适配。
3. **Dusk E2E**:需要宿主 app + 新 chromedriver(原 CI 固定 chromedriver 109)——本轮以 testbench 单测为主网,Dusk 延后单独处理。
4. **larastan level 选择**:level 过高会让 baseline 巨大;起始 level 取实际可达值,后续棘轮上调。
