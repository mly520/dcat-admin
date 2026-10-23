<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\Grid;
use Dcat\Admin3\Grid\Exporters\ExcelExporter;
use Dcat\Admin3\Grid\Tools\BatchActions;
use Dcat\Admin3\Grid\Tools\BatchDelete;
use Dcat\Admin3\Models\Administrator;
use Dcat\Admin3\Support\WebUploader;
use Dcat\Admin3\Tests\Testbench\Fixtures\NullRepository;
use Illuminate\Http\Request;

/**
 * 动态属性自 PHP 8.2 起弃用,PHP 9 将直接抛 Error。
 * 这些路径在每个列表页 / 上传请求都会执行,必须不产生任何 deprecation。
 */
class DynamicPropertyTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        // 列选择器的 SessionStore 依赖 admin guard(宿主 app 中由 admin 配置合并进 auth)
        $app['config']->set('auth.guards.admin', ['driver' => 'session', 'provider' => 'admin']);
        $app['config']->set('auth.providers.admin', ['driver' => 'eloquent', 'model' => Administrator::class]);
    }

    // 分片上传依赖这些字段识别文件与分片序号,必须能正确读出
    public function test_web_uploader_reads_chunk_meta_without_deprecation(): void
    {
        $request = Request::create('/upload', 'POST', [
            '_id' => 'abc',
            'chunk' => 1,
            'chunks' => 3,
            'upload_column' => 'avatar',
        ]);

        $uploader = null;
        $deprecations = $this->collectPhpDeprecations(function () use ($request, &$uploader) {
            $uploader = new WebUploader($request);
        });

        $this->assertSame([], $deprecations);
        $this->assertSame('abc', $uploader->_id);
        $this->assertSame(1, $uploader->chunk);
        $this->assertSame(3, $uploader->chunks);
        $this->assertSame('avatar', $uploader->upload_column);
        $this->assertNull($uploader->file);
    }

    // 每个启用批量操作的列表页都会走 BatchActions::add()
    public function test_batch_actions_add_assigns_selector_prefix_without_deprecation(): void
    {
        $action = new BatchDelete('delete');

        $deprecations = $this->collectPhpDeprecations(function () use ($action) {
            (new BatchActions())->add($action);
        });

        $this->assertSame([], $deprecations);
        $this->assertStringStartsWith('.grid-batch-action-', $action->selectorPrefix);
    }

    // 列选择器的可见列结果按请求缓存,第二次调用应直接返回缓存值
    public function test_grid_caches_visible_columns_without_deprecation(): void
    {
        $this->actingAs((new Administrator())->forceFill(['id' => 1]), 'admin');

        $grid = new Grid(new NullRepository());
        $grid->showColumnSelector();
        $query = request()->query;
        $query->set($grid->getColumnSelectorQueryName(), 'id,name');

        $first = $second = null;
        $deprecations = $this->collectPhpDeprecations(function () use ($grid, $query, &$first, &$second) {
            $first = $grid->getVisibleColumnsFromQuery();
            $query->set($grid->getColumnSelectorQueryName(), 'id');
            $second = $grid->getVisibleColumnsFromQuery();
        });

        $this->assertSame([], $deprecations);
        $this->assertSame(['id', 'name'], $first);
        $this->assertSame($first, $second);
    }

    // 导出按页循环取数:首次缓存去掉分页的查询,之后每页复用;每次导出都会走到
    public function test_exporter_caches_model_queries_without_deprecation(): void
    {
        $grid = new Grid(new NullRepository());
        $exporter = (new ExcelExporter())->setGrid($grid);
        $getGridModel = fn () => $this->getGridModel();

        $first = $second = null;
        $deprecations = $this->collectPhpDeprecations(function () use ($exporter, $getGridModel, &$first, &$second) {
            $first = $getGridModel->call($exporter);
            $second = $getGridModel->call($exporter);
        });

        $this->assertSame([], $deprecations);
        $this->assertSame($grid->model(), $first);
        $this->assertSame($first, $second);
    }

    // 导出每页之间会 reset() 清掉已加载的数据
    public function test_grid_model_reset_without_deprecation(): void
    {
        $model = (new Grid(new NullRepository()))->model();

        $deprecations = $this->collectPhpDeprecations(fn () => $model->reset());

        $this->assertSame([], $deprecations);
    }
}
