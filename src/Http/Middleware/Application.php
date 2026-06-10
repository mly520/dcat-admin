<?php

namespace Dcat\Admin3\Http\Middleware;

use Dcat\Admin3\Admin;

class Application
{
    public function handle($request, \Closure $next, $app = null)
    {
        if ($app) {
            Admin::app()->switch($app);
        }

        return $next($request);
    }
}
