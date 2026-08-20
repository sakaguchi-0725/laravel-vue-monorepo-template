<?php

declare(strict_types=1);

namespace Tests\Architecture;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;
use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

class HttpTest
{
    public function test_admin_and_web_do_not_share_classes(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Http\Admin'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('App\Http\Web'))
            ->because('Admin と Web で Request / Resource を共有しない。公開する項目とバリデーションは画面ごとに変わる');
    }

    public function test_web_does_not_depend_on_admin(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::inNamespace('App\Http\Web'))
            ->shouldNot()
            ->dependOn()
            ->classes(Selector::inNamespace('App\Http\Admin'))
            ->because('Admin と Web で Request / Resource を共有しない。公開する項目とバリデーションは画面ごとに変わる');
    }

    public function test_controllers_are_named_with_controller_suffix(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::extends(Controller::class))
            ->should()
            ->beNamed('/.+Controller$/', true)
            ->because('クラス名は役割を表す suffix で固定する');
    }

    public function test_form_requests_are_named_with_request_suffix(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::extends(FormRequest::class))
            ->should()
            ->beNamed('/.+Request$/', true)
            ->because('クラス名は役割を表す suffix で固定する');
    }

    public function test_resources_are_named_with_resource_suffix(): Rule
    {
        return PHPat::rule()
            ->classes(Selector::extends(JsonResource::class))
            ->should()
            ->beNamed('/.+Resource$/', true)
            ->because('クラス名は役割を表す suffix で固定する');
    }

    public function test_requests_and_resources_live_next_to_their_controller(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::AnyOf(
                    Selector::extends(FormRequest::class),
                    Selector::extends(JsonResource::class),
                ),
            )
            ->should()
            ->beNamed('/^App\\\\Http\\\\(Admin|Web)\\\\[^\\\\]+\\\\(Requests|Resources)\\\\.+$/', true)
            ->because('FormRequest と Resource はコントローラーと同じドメインディレクトリの Requests / Resources に置く');
    }
}
