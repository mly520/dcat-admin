<?php

namespace Dcat\Admin3\Grid\Column\Filter;

use Dcat\Admin3\Grid\Model;

class Lt extends Equal
{
    /**
     * Add a binding to the query.
     *
     * @param  string  $value
     * @param  Model|null  $model
     */
    public function addBinding($value, Model $model)
    {
        $value = trim($value);
        if ($value === '') {
            return;
        }

        $this->withQuery($model, 'where', ['<', $value]);
    }
}
