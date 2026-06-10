<?php

namespace Dcat\Admin3\Form\Field;

use Dcat\Admin3\Form\Field;

class Divide extends Field
{
    public function __construct($label = null)
    {
        $this->label = $label;
    }

    public function render()
    {
        if (! $this->label) {
            return '<hr/>';
        }

        return <<<HTML
<div class="mt-2 text-center mb-2 form-divider">
  <span>{$this->label}</span>
</div>
HTML;
    }
}
