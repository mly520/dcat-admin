<?php

namespace Dcat\Admin3\Http\Actions\Extensions;

use Dcat\Admin3\Admin;
use Dcat\Admin3\Grid\RowAction;

class Disable extends RowAction
{
    public function title()
    {
        return sprintf('<span class="text-80">%s</span>', trans('admin.disable'));
    }

    public function handle()
    {
        Admin::extension()->enable($this->getKey(), false);

        return $this
            ->response()
            ->success(trans('admin.update_succeeded'))
            ->refresh();
    }
}
