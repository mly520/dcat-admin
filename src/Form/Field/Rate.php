<?php

namespace Dcat\Admin3\Form\Field;

class Rate extends Text
{
    public function render()
    {
        $this->prepend('%')->defaultAttribute('placeholder', 0);

        return parent::render();
    }
}
