<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Illuminate\Support\Facades\Gate;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class UseCaseTest
{
    public function test_usecases_are_named_with_usecase_suffix(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::AllOf(
                    Selector::inNamespace('App\UseCases'),
                    Selector::Not(Selector::inNamespace('/^App\\\\UseCases\\\\.+\\\\Dto/', true)),
                ),
            )
            ->should()
            ->beNamed('/^App\\\\UseCases\\\\.+UseCase$/', true)
            ->because('UseCase クラスは 〜UseCase の suffix で固定する');
    }

    public function test_usecases_expose_only_invoke(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname('/^App\\\\UseCases\\\\.+UseCase$/', true))
            ->should()
            ->haveOnlyOnePublicMethodNamed('__invoke')
            ->because('1クラス1ユースケースで、公開する実行メソッドは __invoke のみ');
    }

    public function test_usecases_do_not_depend_on_http_types(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\UseCases'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('Illuminate\Http'),
                Selector::inNamespace('Illuminate\Foundation\Http'),
                Selector::inNamespace('Symfony\Component\HttpFoundation'),
            )
            ->because('FormRequest・Resource・Response などの HTTP 層の型を UseCase に持ち込まない');
    }

    public function test_usecases_do_not_call_other_usecases(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::classname('/^App\\\\UseCases\\\\.+UseCase$/', true))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::classname('/^App\\\\UseCases\\\\.+UseCase$/', true))
            ->because('UseCase から他の UseCase を呼ばない。共有したい処理は Models か Externals に寄せる');
    }

    public function test_authorization_does_not_use_gate_or_policies(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::classname(Gate::class),
                Selector::inNamespace('Illuminate\Auth\Access'),
                Selector::inNamespace('Illuminate\Auth\Middleware'),
            )
            ->because('認可の単位はユースケースなので Policy と Gate は使わない。認可は UseCase の冒頭に早期 return で書く');
    }

    public function test_dtos_are_readonly(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('/^App\\\\UseCases\\\\.+\\\\Dto/', true))
            ->should()
            ->beReadonly()
            ->because('DTO と値オブジェクトは readonly にする');
    }
}
