<?php

namespace Dcat\Admin3\Tests\Testbench;

use Dcat\Admin3\AdminServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AdminServiceProvider::class,
        ];
    }

    protected function collectPhpDeprecations(callable $callback): array
    {
        $messages = [];

        set_error_handler(function ($no, $message) use (&$messages) {
            $messages[] = $message;

            return true;
        }, E_DEPRECATED); // 只收集 PHP 引擎弃用;symfony 7.4 的 Request::get() 等库级弃用(E_USER_DEPRECATED)不在本测试范围

        try {
            $callback();
        } finally {
            restore_error_handler();
        }

        return $messages;
    }
}
