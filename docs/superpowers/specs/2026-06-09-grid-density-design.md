# dcat-admin 新功能:Grid 紧凑模式 + 表头吸顶 — 设计文档

- 日期:2026-06-09
- 分支:`feature/grid-density`
- 包:`dcat/laravel-admin`(框架本体)
- 状态:已确认,待写实现计划

## 背景

信息密集型后台需要在单屏展示更多数据。现有 Grid 默认字号偏大、留白偏多,单屏信息容量低;纵向滚动深层数据时表头滚出视口,字段上下文丢失。

调研现有代码后确认:**多列固定(左右冻结)能力已内置** —— `$grid->fixColumns($head, $tail)`(如 `fixColumns(3, -3)` 左固定 3 列、右固定 3 列,经 `.table-fixed-left/.table-fixed-right` 实现)。因此本轮**不重做列固定**,只补真正缺失的两项:

1. **紧凑/高密度模式**:不存在。
2. **表头吸顶(sticky header)**:不存在。

## 目标与范围

**目标**:为 Grid 新增两个显式开关:
1. `$grid->compact()` —— 高密度模式(缩小字号 + 减小单元格 padding)。
2. `$grid->stickyHeader()` —— 纵向滚动时表头固定吸顶。

**本轮不做(YAGNI)**:重做/增强 `fixColumns`(已可用);全局默认开启(保持向后兼容);把字号/padding 数值做成可配项(先用合理默认,有需要下轮再加);SASS 源改动 + 全主题重编译(改用 `Admin::style()` 内联,见下)。

## 关键决策

- **显式开关,默认关,向后兼容**:两开关默认 `false`,不改变任何现有表格观感。
- **纯 PHP 自包含,不碰 SASS 构建**:CSS 经 `Admin::style()`(框架内置内联样式机制,已有用例 `src/Form/Field/Table.php`、`helpers.php`)注入,作用域限定在语义 class 上。避免改 `resources/assets/dcat/sass/` 源后需 npm/webpack 全量重编译多主题 dist CSS(blue/green/… 多份,重且 diff 巨大)。与上一轮 json 字段"无新前端资源构建"取向一致。

## 组件 1 — `compact()` 开关

`src/Grid.php`(蓝本:现有 `withBorder()` / `scrollbar()`):
- `$options` 默认数组加 `'compact' => false`。
- `public function compact(bool $value = true): static` → `$this->options['compact'] = $value; return $this;`。
- 表格 class 拼接(`formatTableClass()` 或 parent class 方法)在 `compact` 为真时追加 `dcat-grid-compact`。
- 渲染时注入(仅当开启):
  ```css
  .dcat-grid-compact td, .dcat-grid-compact th { padding: .3rem .5rem; font-size: 12px; }
  ```

## 组件 2 — `stickyHeader()` 开关

`src/Grid.php`:
- `$options` 默认加 `'sticky_header' => false`。
- `public function stickyHeader(bool $value = true): static` → 设 `$this->options['sticky_header']`,链式返回。
- 表格容器/表格在开启时追加 class `dcat-grid-sticky-header`。
- 渲染时注入(仅当开启):
  ```css
  .dcat-grid-sticky-header thead th { position: sticky; top: 0; z-index: 3; background: #fff; }
  ```
  - `z-index: 3` 取够高,避免被表体/固定列遮挡;`background` 防止下方行透出。
  - 吸顶相对表格滚动容器生效,配合现有 `scrollbar`/固定高度场景最佳;不依赖 `fixColumns`,可共存。

## 注入位置

集中在 `Grid::render()` 内(`applyFixColumns()` 同一区域)。建议抽一个小私有方法 `applyDensityStyles()`:按 `compact` / `sticky_header` 两个 option 分别 `Admin::style(...)` 注入,保持 `render()` 整洁。class 拼接随现有 `formatTableClass()` / `formatTableParentClass()` 逻辑走。

## 数据流

`$grid->compact()->stickyHeader()` → 设两个 option → `render()` 时:① 按 option 给 `<table>`/容器拼 class;② `applyDensityStyles()` 按 option 调 `Admin::style()` 注入对应 CSS → 浏览器应用。两开关独立,可单开可叠加。

## 边界 / 错误处理

- 默认 false → 现有表格零影响(向后兼容)。
- CSS 限定在 `.dcat-grid-compact` / `.dcat-grid-sticky-header` 作用域,不污染全局 `td/th`。
- 未开启时不注入任何样式(零开销)。
- sticky `z-index` 为经验值;若与 `fixColumns` 克隆表叠加出现遮挡,实测后微调(记录在收尾备注)。

## 测试(testbench,PHP 8.2)

`tests/Testbench/GridDensityTest.php`:
- `compact()` 返回 `$this`(链式);开启后表格 class 含 `dcat-grid-compact`,默认不含。
- `stickyHeader()` 返回 `$this`;开启后容器/表格 class 含 `dcat-grid-sticky-header`,默认不含。
- 渲染开启了 compact/sticky 的 grid 后,`Admin` 收集到的样式包含注入的 CSS 片段(断言 `.dcat-grid-compact` / `position: sticky` 出现);未开启时不出现。

验证(整体):
- testbench 全绿:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpunit -c phpunit.xml`
- larastan 相对 baseline **零新增**:`/opt/homebrew/opt/php@8.2/bin/php vendor/bin/phpstan analyse --memory-limit=1G`
- 宿主 app 实测:`dcat-host` 的 `ShowcaseItemController` grid 加 `->compact()->stickyHeader()`,浏览器看字号变小、纵向滚动表头吸顶。

> 注意:所有 PHP 命令用 `/opt/homebrew/opt/php@8.2/bin/php`(默认 php 为 EOL 的 8.0)。

## 涉及文件

- 修改:`src/Grid.php`(2 个开关方法 + 2 个 options 默认 + class 拼接 + `applyDensityStyles()` + 在 render 调用)
- 测试:`tests/Testbench/GridDensityTest.php`
- (实测,非框架代码)宿主 app `ShowcaseItemController`
