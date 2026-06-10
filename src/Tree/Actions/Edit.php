<?php

namespace Dcat\Admin3\Tree\Actions;

use Dcat\Admin3\Tree\RowAction;

class Edit extends RowAction
{
    public function html()
    {
        return <<<HTML
<a href="{$this->resource()}/{$this->getKey()}/edit"><i class="feather icon-edit-1"></i>&nbsp;</a>
HTML;
    }
}
