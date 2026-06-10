<?php

namespace Dcat\Admin3\Tree;

use Dcat\Admin3\Actions\Action;
use Dcat\Admin3\Tree;

abstract class AbstractTool extends Action
{
    /**
     * @var Tree
     */
    protected $parent;

    /**
     * @var string
     */
    protected $style = 'btn btn-sm btn-primary';

    /**
     * @param  Tree  $parent
     * @return void
     */
    public function setParent(Tree $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return void
     */
    public function setupHtmlAttributes()
    {
        $this->addHtmlClass($this->style);

        parent::setupHtmlAttributes();
    }
}
