<?php

namespace Dcat\Admin3\Grid\Actions;

use Dcat\Admin3\Grid\RowAction;

class Edit extends RowAction
{
    /**
     * @return array|null|string
     */
    public function title()
    {
        if ($this->title) {
            return $this->title;
        }

        return '<i class="feather icon-edit-1"></i> '.__('admin.edit').' &nbsp;&nbsp;';
    }

    /**
     * @return string
     */
    public function href()
    {
        return $this->parent->getEditUrl($this->getKey());
    }
}
