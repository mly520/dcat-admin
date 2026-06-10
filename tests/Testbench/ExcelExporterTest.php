<?php

namespace Dcat\Admin3\Tests\Testbench;

class ExcelExporterTest extends TestCase
{
    public function test_excel_exporter_class_loads(): void
    {
        $this->assertTrue(class_exists(\Dcat\Admin3\Grid\Exporters\ExcelExporter::class));
    }

    public function test_easy_excel_dependency_is_available(): void
    {
        $this->assertTrue(
            class_exists(\Dcat\EasyExcel\Excel::class),
            'dcat/easy-excel 未安装或不兼容当前 PHP'
        );
    }
}
