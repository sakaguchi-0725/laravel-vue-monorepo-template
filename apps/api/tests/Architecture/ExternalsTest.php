<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ExternalsTest
{
    public function test_client_interfaces_are_named_with_client_suffix(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::AllOf(
                    Selector::inNamespace('App\Externals'),
                    Selector::isInterface(),
                ),
            )
            ->should()
            ->beNamed('/.+Client$/', true)
            ->because('外部サービスの interface は 〜Client と命名する');
    }

    public function test_usecases_do_not_depend_on_client_implementations(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\UseCases'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname('/^App\\\\Externals\\\\.+\\\\Http.+Client$/', true))
            ->because('UseCase は interface のみに依存し、HTTP 実装クラスを直接参照しない');
    }

    public function test_service_container_bindings_are_not_registered_in_app_service_provider(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname('App\Providers\AppServiceProvider'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('App\Externals'))
            ->because('Externals の DI 登録は専用の ServiceProvider に集約する');
    }
}
