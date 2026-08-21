<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Exceptions\ApplicationException;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class ExceptionTest
{
    public function test_exceptions_do_not_depend_on_http_types(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Exceptions'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('Illuminate\Http'),
                Selector::inNamespace('Illuminate\Foundation\Http'),
                Selector::inNamespace('Symfony\Component\HttpFoundation'),
                Selector::inNamespace('Symfony\Component\HttpKernel'),
            )
            ->because('エラークラスは HTTP ステータスを知らない。ステータスへの変換は Http 層の ErrorResponseFactory に閉じる');
    }

    public function test_exceptions_do_not_depend_on_other_layers(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Exceptions'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('App\Http'),
                Selector::inNamespace('App\UseCases'),
                Selector::inNamespace('App\Models'),
                Selector::inNamespace('App\Externals'),
            )
            ->because('Exceptions は全レイヤーから投げられるため、どのレイヤーにも依存しない');
    }

    public function test_exceptions_are_named_with_exception_suffix_in_the_exceptions_namespace(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::extends(ApplicationException::class))
            ->should()
            ->beNamed('/^App\\\\Exceptions\\\\.+Exception$/', true)
            ->because('業務例外は App\\Exceptions に集約し、〜Exception の suffix で固定する');
    }
}
