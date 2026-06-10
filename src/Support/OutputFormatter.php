<?php

namespace Dcat\Admin3\Support;

class OutputFormatter extends \Symfony\Component\Console\Formatter\OutputFormatter
{
    public function format(?string $message): ?string
    {
        return $message;
    }
}
