<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class LayerDependencyTest
{
    public function test_http_depends_only_on_usecases(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Http'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('App\Models'),
                Selector::inNamespace('App\Externals'),
            )
            ->because('依存方向は Http → UseCases → Models / Externals の一方向のみで、レイヤを飛ばした参照は作らない');
    }

    public function test_usecases_do_not_depend_on_http(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\UseCases'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('App\Http'))
            ->because('UseCase は呼び出し元の HTTP 層を知らない');
    }

    public function test_models_do_not_depend_on_other_layers(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Models'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('App\Http'),
                Selector::inNamespace('App\UseCases'),
                Selector::inNamespace('App\Externals'),
            )
            ->because('Models は最下層で、他のレイヤを参照しない');
    }

    public function test_externals_do_not_depend_on_other_layers(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Externals'))
            ->shouldNot()
            ->dependOn()
            ->classes(
                Selector::inNamespace('App\Http'),
                Selector::inNamespace('App\UseCases'),
                Selector::inNamespace('App\Models'),
            )
            ->because('Externals は最下層で、他のレイヤを参照しない');
    }
}
