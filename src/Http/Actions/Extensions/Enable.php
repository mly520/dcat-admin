<?php

namespace Dcat\Admin3\Http\Actions\Extensions;

use Dcat\Admin3\Admin;
use Dcat\Admin3\Grid\RowAction;

class Enable extends RowAction
{
    public function title()
    {
        return sprintf('<b>%s</b>', trans('admin.enable'));
    }

    public function handle()
    {
        Admin::extension()->enable($this->getKey());

        return $this
            ->response()
            ->success(trans('admin.update_succeeded'))
            ->refresh();
    }
}
