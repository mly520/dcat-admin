<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Illuminate\Support\Arr;

class Json extends Field
{
    protected function prepareInputValue($value)
    {
        if ($value === null || is_array($value)) {
            return $value;
        }
        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }
        $decoded = json_decode($trimmed, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
