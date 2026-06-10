<?php

namespace Dcat\Admin3\Http\Actions\Extensions;

use Dcat\Admin3\Admin;
use Dcat\Admin3\Grid\RowAction;

class Update extends RowAction
{
    public function title()
    {
        $replace = ['version' => $this->row->extension->getLocalLatestVersion()];

        return sprintf('<b>%s</b>', trans('admin.upgrade_to_version', $replace));
    }

    public function handle()
    {
        $manager = Admin::extension()
            ->updateManager()
            ->update($this->getKey());

        return $this
            ->response()
            ->success(implode('<br>', $manager->notes))
            ->refresh();
    }
}
