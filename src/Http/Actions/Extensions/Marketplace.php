<?php

namespace Dcat\Admin3\Http\Actions\Extensions;

use Dcat\Admin3\Grid\Tools\AbstractTool;

class Marketplace extends AbstractTool
{
    protected $style = 'btn btn-primary';

    public function title()
    {
        return '<i class="feather icon-shopping-cart"></i> &nbsp;'.trans('admin.marketplace');
    }

    public function html()
    {
        return parent::html().'&nbsp;';
    }
}
