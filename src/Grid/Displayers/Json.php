<?php

namespace Dcat\Admin3\Grid\Displayers;

class Json extends AbstractDisplayer
{
    public function display()
    {
        $value = $this->value;

        if ($value === null || $value === '') {
            return '';
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return e($value);
            }
            $value = $decoded;
        }

        $formatted = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // json_encode 对无效 UTF-8 等会返回 false:降级为可读 dump 转义,不静默丢数据
        if ($formatted === false) {
            $fallback = is_string($this->value) ? $this->value : var_export($this->value, true);

            return e($fallback);
        }

        return '<pre class="dcat-json-display" style="margin:0;white-space:pre-wrap;word-break:break-all;">'.htmlspecialchars($formatted, ENT_NOQUOTES, 'UTF-8').'</pre>';
    }
}
