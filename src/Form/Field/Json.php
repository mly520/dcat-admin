<?php

namespace Dcat\Admin\Form\Field;

use Dcat\Admin\Form\Field;
use Illuminate\Support\Arr;

class Json extends Field
{
    public function getValidator(array $input)
    {
        if ($this->validator) {
            return $this->validator->call($this, $input);
        }

        if (! is_string($this->column) || ! Arr::has($input, $this->column)) {
            return false;
        }

        $value = Arr::get($input, $this->column);
        $rules = [
            $this->column => function ($attribute, $val, $fail) {
                if ($val === null || $val === '' || (is_string($val) && trim($val) === '')) {
                    return;
                }
                if (! is_string($val)) {
                    return;
                }
                json_decode($val);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    $fail('The :attribute must be valid JSON.');
                }
            },
        ];

        return validator(
            [$this->column => $value],
            $rules,
            $this->getValidationMessages(),
            [$this->column => $this->label]
        );
    }

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
