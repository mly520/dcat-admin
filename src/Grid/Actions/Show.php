<?php

namespace Dcat\Admin3\Grid\Actions;

use Dcat\Admin3\Grid\RowAction;

class Show extends RowAction
{
    /**
     * @return array|null|string
     */
    public function title()
    {
        if ($this->title) {
            return $this->title;
        }

        return '<i class="feather icon-eye"></i> '.__('admin.show').' &nbsp;&nbsp;';
    }

    /**
     * @return string
     */
    public function href()
    {
        return $this->parent->urlWithConstraints("{$this->resource()}/{$this->getKey()}");
    }
}
