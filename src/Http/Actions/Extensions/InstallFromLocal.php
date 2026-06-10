<?php

namespace Dcat\Admin3\Http\Actions\Extensions;

use Dcat\Admin3\Grid\Tools\AbstractTool;
use Dcat\Admin3\Http\Forms\InstallFromLocal as InstallFromLocalForm;
use Dcat\Admin3\Widgets\Modal;

class InstallFromLocal extends AbstractTool
{
    protected $style = 'btn btn-primary';

    public function html()
    {
        return Modal::make()
            ->lg()
            ->title($title = trans('admin.install_from_local'))
            ->body(InstallFromLocalForm::make())
            ->button("<button class='btn btn-primary'><i class=\"feather icon-folder\"></i> &nbsp;{$title}</button> &nbsp;");
    }
}
