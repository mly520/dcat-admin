# dcat-admin 新功能:Form `json` 字段 + Grid `->json()` 显示器 — 设计文档

- 日期:2026-06-09
- 分支:`feature/json-field`
- 包:`dcat/laravel-admin`(框架本体)
- 状态:已确认,待写实现计划

## 背景

dcat-admin 现有 `keyValue`(只能编平铺的字符串 `key => value`)和 `embeds`(需预定义子字段),没有"直接编辑任意嵌套 JSON 列"的字段。很多项目有 json/array cast 的列(本仓库测试用的 `showcase_items.specs` 即是),目前只能用 `keyValue` 凑合。本功能补上一个通用 JSON 编辑字段,并配套一个列表展示器。

升级已完成(PHP `^8.2` + Laravel `^11|^12`),testbench 单测 + larastan baseline 已就位,具备 TDD + 验证条件。

## 目标与范围

**目标**:为框架新增
1. Form 字段 `json` —— 友好的嵌套 JSON 编辑器(textarea + 客户端校验/格式化,不引入新前端资源)。
2. Grid 列显示器 `->json()` —— 列表中美化展示 JSON。

**本轮不做(YAGNI)**:语法高亮编辑器(CodeMirror 等需打包的资源);`->saveAsString()` 等可配置存储模式(默认解码成数组);JSON Schema 校验;树形折叠交互。

## 存储契约(已确认)

- 提交:textarea 中的 JSON 字符串 → 空白输入存 `null`;否则 `json_decode($v, true)` 成数组交给模型,**列需 json/array cast**(与 `keyValue` 一致,模型 cast 负责再编码)。
- 校验:非法 JSON → **服务端字段级校验错误,阻止保存**(客户端格式化按钮提供即时提示,但服务端是最终关卡)。
- 回显:列值(array)→ `json_encode(PRETTY_PRINT | UNESCAPED_UNICODE | UNESCAPED_SLASHES)` 填入 textarea。

## 组件 1 — Form 字段 `Json`

**文件**:`src/Form/Field/Json.php`(继承 `Dcat\Admin\Form\Field`,以 `src/Form/Field/KeyValue.php` 为蓝本)

职责:
- `formatFieldData($data)`:取列值并归一化为可编码的数据(array)。
- `render()`:把当前值 `json_encode` 成 pretty 字符串注入视图变量 `formatted`,调用 `parent::render()`。视图名由基类 `view()` 推导为 `admin::form.json`。
- `prepareInputValue($value)`:空白 → `null`;否则 `json_decode($value, true)` 返回数组。
- `getValidator(array $input)`(仿 KeyValue):对本列加一条校验——值为空或为合法 JSON;非法时返回失败的 validator,错误归到该字段。

**视图**:`resources/views/form/json.blade.php`
- 复用 `admin::form.error`、`admin::form.help-block` 分区。
- `<textarea name="{{$name}}">{{ $formatted }}</textarea>`。
- 一个「格式化」按钮 + 内联 `<script>`:点按时 `JSON.parse` 输入,成功则 `JSON.stringify(obj, null, 2)` 回填、清除错误样式;失败则给 textarea 加红边 + 显示「JSON 格式错误」。纯内联 JS,无新资源。

**注册**:`src/Form.php`
- `$availableFields` 数组加 `'json' => Field\Json::class`。
- 类顶部 `@method` 注解加 `Field\Json json($column, $label = '')`(IDE 友好,与现有注解风格一致)。

## 组件 2 — Grid 显示器 `Json`

**文件**:`src/Grid/Displayers/Json.php`(继承 `Dcat\Admin\Grid\Displayers\AbstractDisplayer`)

职责:
- 取单元格值;若是数组/对象直接用,若是字符串则尝试 `json_decode`;归一化后 `json_encode` 成 pretty 字符串。
- 输出 `<pre class="...">{{ 转义后的 pretty JSON }}</pre>`(`e()` 转义防 XSS)。
- 非法 JSON 字符串 → 原样显示;`null`/空 → 显示空。安全降级,不抛异常。

**注册**:`src/Grid/Column.php`
- `$displayers` 数组加 `'json' => Displayers\Json::class`。
- 类顶部 `@method` 注解加 `$this json()`。

## 数据流

- **回显**:模型列(array,cast)→ `Json::render` → pretty JSON → textarea。
- **提交**:textarea 串 → `getValidator` 校验(合法 JSON 或空)→ `prepareInputValue` → `json_decode` 成 array → 模型(array cast)→ DB。
- **列表**:模型列(array 或 string)→ `->json()` → 归一化 array → pretty `<pre>`。

## 错误处理

- 非法 JSON 提交 → 服务端校验拦截,字段红字,save 失败。
- 客户端「格式化」按钮:解析失败给即时提示(UX),不阻断提交本身(服务端兜底)。
- 空输入 → 存 `null`。
- Grid 遇非数组/非法 JSON 值 → 原样字符串显示,不崩。

## 测试(testbench,PHP 8.2)

字段 `tests/Testbench/JsonFieldTest.php`:
- `$form->json('specs')` 返回 `Field\Json` 实例(注册生效)。
- `render()` 输出含 `<textarea`,且包含默认数组的 pretty JSON 文本。
- `prepareInputValue`:合法 JSON 串 → 对应数组;空串/空白 → `null`。
- `getValidator`:非法 JSON 输入 → validator fails;合法/空 → 不 fail(或 false 表示跳过)。

显示器 `tests/Testbench/JsonDisplayerTest.php`:
- `$grid->column('specs')->json()` 对 array 值渲染含 `<pre>` 的 pretty JSON。
- 对合法 JSON 字符串值同样美化;对非法字符串/`null` 安全降级不抛异常。

验证(整体):
- testbench 全绿:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml`
- larastan 相对 baseline **零新增**:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G`
- 宿主 app 实测:`dcat-host` 的 `ShowcaseItemController` 把 `specs` 的 `keyValue` 换成 `json()`,Grid 加 `->json()`,浏览器编辑/保存/列表展示验证。

> 注意:所有 PHP 命令用 `/opt/homebrew/opt/php@8.2/bin/php`(默认 php 为 EOL 的 8.0)。

## 涉及文件

- 新增:`src/Form/Field/Json.php`、`resources/views/form/json.blade.php`、`src/Grid/Displayers/Json.php`
- 修改:`src/Form.php`(注册 + `@method`)、`src/Grid/Column.php`(注册 + `@method`)
- 测试:`tests/Testbench/JsonFieldTest.php`、`tests/Testbench/JsonDisplayerTest.php`
