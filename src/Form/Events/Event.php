<?php

namespace Dcat\Admin3\Form\Events;

use Dcat\Admin3\Form;

abstract class Event
{
    /**
     * @var Form
     */
    public $form;

    public $payload = [];

    public function __construct(Form $form, array $payload = [])
    {
        $this->form = $form;
        $this->payload = $payload;
    }
}
