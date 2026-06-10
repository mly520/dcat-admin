<?php

namespace Dcat\Admin3\Contracts;

use Dcat\Admin3\Form\Field;
use Illuminate\Support\Collection;

interface FieldsCollection
{
    /**
     * Get fields of this builder.
     *
     * @return Collection
     */
    public function fields();

    /**
     * Get specify field.
     *
     * @param  string|Field  $name
     * @return Field|null
     */
    public function field($name);
}
