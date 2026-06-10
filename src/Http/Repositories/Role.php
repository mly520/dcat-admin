<?php

namespace Dcat\Admin3\Http\Repositories;

use Dcat\Admin3\Repositories\EloquentRepository;

class Role extends EloquentRepository
{
    public function __construct($relations = [])
    {
        $this->eloquentClass = config('admin.database.roles_model');

        parent::__construct($relations);
    }
}
