# PHP 8.5 / Laravel 13 支持 — 设计与结果记录

- 日期:2026-10-23
- 分支:`upgrade/php85-laravel13`(基于 `3.0`)
- 前一轮:[2026-06-09 PHP/Laravel 升级](./2026-06-09-php-laravel-upgrade-design.md)(PHP 8.2 + Laravel 11/12)

## 支持矩阵

| | 3.0 升级前 | 本次之后 |
|---|---|---|
| PHP | ^8.2 | **^8.3**(8.3 / 8.4 / 8.5) |
| Laravel | ^11.0 \| ^12.0 | **^12.0 \| ^13.0** |
| orchestra/testbench | ^9 \| ^10 | ^10 \| ^11 |
| phpunit | ^11 | ^11 \| ^12 |
| spatie/eloquent-sortable | ^4 | ^4 \| ^5 |

## 决策

1. **放弃 PHP 8.2**:Laravel 13 要求 PHP ≥ 8.3,维持 8.2 只为 L12 一条线,收益低。
2. **放弃 Laravel 11**:已 EOL,且所有 11.x 版本都带未修复的安全公告,Composer 默认(`policy.advisories.block`)拒绝安装,CI 无法再测。
3. **库级弃用不批量替换**:`Illuminate\Http\Request::get()` 在 L13 被标为 `@deprecated`(Laravel 自行实现,运行期不报);在 L12 + symfony/http-foundation 7.4 下运行期会发出 `E_USER_DEPRECATED`。`src/` 有 52 处调用,但 `get()`(attributes → query → body)与 `input()`(body 优先于 query)取值优先级不同,批量替换有行为风险,留作后续单独处理。
4. **测试只断言 PHP 引擎弃用**:新增测试通过 `TestCase::collectPhpDeprecations()` 收集 `E_DEPRECATED`,不收集第三方库的 `E_USER_DEPRECATED`,保证 L12 / L13 下结论一致。

## 改动

### 依赖与 CI
- `composer.json` 按上表调整。
- `.github/workflows/ci.yml`:矩阵 PHP 8.3/8.4/8.5 × Laravel 12/13,每格先 `composer require laravel/framework:^N.0 --no-update` 再 `composer update`。
- 另加一格 `lowest-majors`(PHP 8.3 + L12 + spatie/eloquent-sortable ^4 + phpunit ^11 + testbench ^10),覆盖 composer.json 声明的下限主版本。

### 编译期弃用(lint 可发现)
- 51 处隐式可空参数 `Type $x = null` → `?Type $x = null`(PHP 8.4 起弃用),用 php-cs-fixer 单一规则 `nullable_type_declaration_for_default_null_value` 机械修复。
- 测试中移除 `ReflectionProperty::setAccessible()`(8.1 起无效,8.5 弃用)。

### 运行期弃用(只有执行到才会出现)
大部分是在宿主应用里实际操作时从 `storage/logs` 中发现的:

| 位置 | 问题 | 修复 |
|---|---|---|
| `Support\Setting::add()` | 条件写反:不传 `$k` 时执行 `$results[null] = ...`(8.5 弃用,且值落到 `''` 键) | 改为 `$k === null` 时追加。**这是真 bug** |
| `Support\WebUploader` | `_id/chunk/chunks/upload_column/file` 只在 `@property` 中声明 | 显式声明属性 |
| `Grid\Tools\BatchActions::add()` | 往 `BatchAction` 上动态写 `$selectorPrefix`,但全仓库(PHP / 视图 / JS)无任何读取 | 删除该无效写入 |
| `Grid\Concerns\CanHidesColumns` | `$visibleColumnsFromQuery` 缓存未声明 | 声明属性 |
| `Grid\Exporters\AbstractExporter` | `$modelQueries` 缓存未声明;且 `setQueries()` 直接持有缓存,每页的 `forPage` 被累积进缓存 | 声明属性;每页使用缓存的副本 `clone` |
| `Grid\Model::reset()` | 写入从未声明、从未读取的 `$model` | 删除该无效写入 |
| `Form\Field\File` | 未上传文件时 `explode(',', null)` | `(string) $value` |
| `Form\Field\Tags` / `Form\Field\Select` | `array_filter($v, 'strlen')` 遇 null 元素(Select 经 `replaceOptions()` 可传入 null 标签) | 改用已有的 `Helper::array()`(过滤 `''`/null,保留键与 `'0'`) |
| `Console/stubs/metrics/Sessions.stub` | `option` 缺失或为空(`?option=` 经 `ConvertEmptyStringsToNull` 变 null)时 null 作数组下标 | `(string) request()->input('option')` |

动态属性在 PHP 8.2 起弃用、PHP 9 将直接抛 `Error`。`phpstan-baseline.neon` 里原本有上述属性的 `property.notFound` 条目,掩盖了这些问题;剩余同类条目已逐一核对,均为 Eloquent 模型属性或带 `__get/__set` 的类,不会触发弃用。

### 其它修复(代码审查发现)

| 位置 | 问题 | 修复 |
|---|---|---|
| `Support\WebUploader::mergeChunks()` | **安全**:请求中的 `_id` 未校验就拼进临时目录,`_id=../../public/x` 可在 storage 之外建目录并写入分片(原有问题,测试中已复现) | 只允许由 `.` 分隔的 `[A-Za-z0-9_-]` 段,否则 `abort(422)` |
| `Form\Field\ListField::formatValidatorMessages()` | 把每项的错误数组整体作为一条消息 `add()`,结果嵌套;前端 `showError()` 期望字符串数组,多条错误被 `toString()` 拼成一行 | 逐条 `add()`。L13 下 phpstan 报的 `MessageBag::add()` 类型错误随之消失,不再需要忽略规则 |

### phpstan
- baseline 随 L13 / larastan 新报错文本重新生成,并删除已修复条目。`phpstan.neon` 与升级前一致。

## 对下游的影响(升级注意)

1. **新声明的属性**:以下属性原本是动态属性,现在显式声明。宿主应用或扩展中继承这些类、并声明了**同名但可见性不同或带类型**的属性时,会在加载时报致命错误,需要调整为相同声明:
   - `Support\WebUploader`:`public $_id`、`$chunk`、`$chunks`、`$upload_column`、`$file`
   - `Grid\Concerns\CanHidesColumns`(即 `Grid`):`protected $visibleColumnsFromQuery`
   - `Grid\Exporters\AbstractExporter`:`protected $modelQueries`
2. **`BatchAction::$selectorPrefix` 不再被赋值**:框架内无人读取;如果扩展读取了它,需要改为自行维护。
3. **`Setting::add($key, $value)` 行为修正**:旧版不传 `$k` 时把值存到 `''` 键(每次覆盖),传 `$k` 时反而追加;现在按注释语义,不传 `$k` 追加、传 `$k` 按键写入。按旧行为已存入 `admin_settings` 的数据可能含 `""` 键,调用方需自行清理。框架自身未调用此方法。
4. **`ListField` 校验错误格式**:`$errors->get('字段')` 从嵌套数组变为字符串数组。
5. **分片上传**:`_id` 不符合上述格式的请求返回 422。框架生成的 `_id`(`Str::random()` / 前端「字段名 + 随机串」)均符合。

### 宿主应用自身的 PHP 8.5 弃用(与本包无关,但升级时必然遇到)

本包 `src/` / `config/` 不使用任何 PDO 驱动常量。宿主应用若沿用旧版 Laravel 骨架的 `config/database.php`,PHP 8.5 下会报:

```
Deprecated: Constant PDO::MYSQL_ATTR_SSL_CA is deprecated since 8.5, use Pdo\Mysql::ATTR_SSL_CA instead
```

- PHP 8.5 共弃用 23 个 PDO 驱动常量:`PDO::MYSQL_*` 16 个、`PDO::SQLITE_*` 7 个;新常量 `Pdo\Mysql::*` / `Pdo\Sqlite::*` 与旧常量值相同。
- 新常量所在的 `Pdo\Mysql` 类自 PHP 8.4 起才存在,PHP 8.3 下直接引用会抛 `Class "Pdo\Mysql" not found`。
- 需要同时支持 PHP 8.3 时使用三元写法(已实测:PHP 8.5 下 opcache 开 / 关均不触发弃用,未选中的分支不会被求值):

```php
'options' => extension_loaded('pdo_mysql') ? array_filter([
    (PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA) => env('MYSQL_ATTR_SSL_CA'),
]) : [],
```

- Laravel 13 新骨架直接 `use Pdo\Mysql;` 并写 `Mysql::ATTR_SSL_CA`,只适用于 PHP ≥ 8.4。
- 修改后仍出现警告时,通常是 PHP-FPM 的 opcache 仍缓存旧文件,重启 PHP-FPM 即可。

## 验证

1. **testbench 单测 + phpstan**:PHP 8.3 / 8.4 / 8.5 × Laravel 12 / 13 共 6 个组合,每组独立 `composer update`,全部测试通过,phpstan `No errors`;另在 PHP 8.3 + L12 上用下限主版本(spatie 4.5 / phpunit 11.5 / testbench 10.12)跑通。
2. **宿主应用实测**(Laravel 13.38 + PHP 8.5 + MySQL 8.0,`LOG_DEPRECATIONS_CHANNEL=single`):
   - `admin:publish` → `migrate` → `admin:install`
   - 内置页面:仪表盘、管理员/角色/权限(列表、新建、详情、编辑)、菜单树、扩展
   - 演示资源(文本/下拉/开关/数字/金额/日期/标签/键值/图片/文件/多图/富文本,Tab 布局):排序、筛选、快速搜索、分页、列选择器、新建 + 上传、编辑保存、行内开关、Excel 导出、批量删除
   - 最终一轮日志中,本包代码零告警(仅剩第三方依赖,见下)

### 本地复现

本机没有 PHP 8.5 时可用 Docker:

```dockerfile
FROM php:8.5-cli
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions zip gd bcmath pdo_mysql intl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apt-get update && apt-get install -y git unzip && rm -rf /var/lib/apt/lists/*
```

```bash
docker build -t dcat-php85 .
docker run --rm -v "$PWD":/app -w /app dcat-php85 sh -c \
  'composer update && php -d error_reporting=-1 vendor/bin/phpunit --display-deprecations && vendor/bin/phpstan analyse --memory-limit=1G'
```

找编译期弃用:

```bash
find src tests -name '*.php' | while read f; do php -d error_reporting=-1 -l "$f" | grep -v '^No syntax errors'; done
```

## 遗留 / 后续

1. **`dcat/easy-excel` 与 `box/spout`**:导出时两者都有隐式可空参数弃用(PHP 8.4+)。功能正常,但 PHP 9 会失败。`box/spout` 已 abandoned,建议替换为 `openspout/openspout` 或 fork `easy-excel`。
2. **`Request::get()` → `input()`**:见决策 3,需逐处确认取值来源后迁移。
3. **其它运行期弃用**:本轮只覆盖了实测路径,未执行到的字段/组件仍可能存在 `Passing null to parameter` 类问题;建议在宿主应用中开启 `LOG_DEPRECATIONS_CHANNEL` 持续观察。
4. **Dusk E2E**:仍未恢复(沿用上一轮结论)。
