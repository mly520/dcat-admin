<?php

namespace Dcat\Admin3\Grid\Displayers;

use Dcat\Admin3\Admin;

class Select extends AbstractDisplayer
{
    public function display($options = [], $refresh = false)
    {
        if ($options instanceof \Closure) {
            $options = $options->call($this, $this->row);
        }

        return Admin::view('admin::grid.displayer.select', [
            'column'  => $this->column->getName(),
            'value'   => $this->value,
            'url'     => $this->url(),
            'options' => $options,
            'refresh' => $refresh,
        ]);
    }

    protected function url()
    {
        return $this->resource().'/'.$this->getKey();
    }
}
