# dcat-admin3 新功能:Form 紧凑/高密度模式 — 设计文档

- 日期:2026-06-12
- 分支:`feature/form-compact`
- 包:`dcat/admin3`(框架本体,命名空间 `Dcat\Admin3\`)
- 状态:已确认(含浏览器实时预览),待写实现计划

## 背景

字段多的复杂表单(如 7 Tab / 40+ 字段)在默认间距下纵向占用很大,单屏看不全。Grid 已在 v3.0.0-dev 加了 `compact()` 紧凑模式;Form 缺同类能力。

调研现有代码:Form 的**灵活排版能力已齐全** —— `row()`(同行多字段)、`column()`/`block()`(多列/分块)、`tab()`、`fieldset()`、`width($field,$label)`(Bootstrap 栅格宽度)。因此本轮**不重做排版**,只补缺失的**紧凑/高密度模式**(缩小字号 + 压缩间距)。

浏览器实时注入提案 CSS 预览确认:基础 Tab 9 字段整体高度从占满屏压到约 2/3,可读性保留。

## 目标与范围

**目标**:新增开关 `$form->compact()` —— 开启后缩小表单字号、压缩 label/控件/form-group 间距,提升多字段表单的单屏信息容量。

**本轮不做(YAGNI)**:重做/增强现有排版(row/column/block/tab 已可用);可调密度级别参数;全局 config 默认紧凑(保持向后兼容);SASS 源改动(改用 `Admin::style()` 内联)。

## 关键决策

- **显式开关,默认关,向后兼容**:`compact()` 默认 `false`,不改变任何现有表单观感。
- **纯 PHP 自包含,不碰 SASS 构建**:CSS 经 `Admin::style()` 内联注入(框架内置机制,Builder 已 `use Dcat\Admin3\Admin`),作用域限定在 `.dcat-form-compact`。与 Grid `compact()` 完全同套路。

## 组件 1 — `Form::compact()` 开关

`src/Form.php`(蓝本:Form 现有向 builder 转发的方法):
- `public function compact(bool $value = true)`:转发到 builder 存标志,链式返回 `$this`。

`src/Form/Builder.php`:
- 加 `protected $compact = false;` + `public function compact(bool $value = true)`(setter)+ `public function isCompact()`(getter)。

## 组件 2 — class 注入

`src/Form/Builder.php`:
- 表单 `<form>` 标签的 class 拼接处(约 line 713 `$this->open(['class' => 'form-horizontal'])`),当 `isCompact()` 为真时把 class 改为 `'form-horizontal dcat-form-compact'`。

## 组件 3 — CSS 注入

`src/Form/Builder.php`:渲染时(build/render 区)当 `isCompact()` 为真,`Admin::style()` 注入(仅开启时,零开销),作用域 `.dcat-form-compact`:

```css
.dcat-form-compact .form-group { margin-bottom: .5rem; }
.dcat-form-compact .form-control, .dcat-form-compact label, .dcat-form-compact .form-label { font-size: 12px; }
.dcat-form-compact .form-control { padding: .25rem .5rem; height: auto; min-height: calc(1.5em + .5rem); }
.dcat-form-compact textarea.form-control { min-height: 60px; }
.dcat-form-compact .help-block { font-size: 11px; margin-top: 2px; }
.dcat-form-compact .control-label, .dcat-form-compact .col-form-label { padding-top: .25rem; padding-bottom: .25rem; }
```

数值取自浏览器预览验证过的值,保证可读。

## 数据流

`$form->compact()` → builder 存标志 → 渲染时:① `<form>` 加 `dcat-form-compact` class;② `Admin::style()` 注入 CSS → 浏览器应用。默认关时零 class、零注入。

## 边界 / 错误处理

- 默认 false → 现有表单零影响(向后兼容)。
- CSS 限定 `.dcat-form-compact` 作用域,不污染全局 `.form-group`/`.form-control`。
- 与现有 tab / column / block / row / fieldset 排版可叠加共存。
- 未开启时不注入任何样式(零开销)。

## 测试(testbench,PHP 8.2)

`tests/Testbench/FormCompactTest.php`:
- `compact()` 返回 `$this`(链式)。
- 开启后渲染的表单 HTML 含 `dcat-form-compact`;默认不含。
- 渲染开启 compact 的表单后,`Admin::asset()->styleToHtml()` 含注入的 CSS 片段(断言 `.dcat-form-compact` 出现);未开启时不含。

> 渲染测试以能拿到表单 HTML / Admin 收集到的样式为准;若直接渲染整表单在 testbench 下依赖较多,可退而构造最小 Form + 一两个字段渲染。实现时据实调整,保持"class 注入 + CSS 注入"两个意图断言不变。

验证(整体):
- testbench 全绿:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml`
- phpstan 相对 baseline **零新增**:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G`
- 宿主 app 实测:`dcat-host` 的 `ShowcaseItemController` 表单加 `->compact()`,浏览器看字号变小、间距压缩、可读(对照已做的预览)。

> 注意:所有 PHP 命令用 `/opt/homebrew/opt/php@8.2/bin/php`(默认 php 为 EOL 的 8.0)。

## 涉及文件

- 修改:`src/Form.php`(compact 转发)、`src/Form/Builder.php`(标志 + class 注入 + applyCompactStyle)
- 测试:`tests/Testbench/FormCompactTest.php`
- (实测,非框架代码)宿主 app `ShowcaseItemController`
