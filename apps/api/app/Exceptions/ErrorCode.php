<?php

declare(strict_types=1);

namespace App\Exceptions;

enum ErrorCode: string
{
    case InvalidArguments = 'INVALID_ARGUMENTS';
    case PermissionDenied = 'PERMISSION_DENIED';
    case NotFound = 'NOT_FOUND';
    case Conflict = 'CONFLICT';
    case InternalError = 'INTERNAL_ERROR';

    public function defaultMessage(): string
    {
        return match ($this) {
            self::InvalidArguments => 'リクエストの内容が正しくありません。',
            self::PermissionDenied => 'この操作を行う権限がありません。',
            self::NotFound => '対象が見つかりません。',
            self::Conflict => '現在の状態ではこの操作を行えません。',
            self::InternalError => 'サーバー内部でエラーが発生しました。',
        };
    }
}
