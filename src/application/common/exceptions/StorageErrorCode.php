<?php

declare(strict_types=1);

namespace app\application\common\exceptions;

enum StorageErrorCode: string
{
    case InvalidStream = 'file.error.content_invalid_stream';
    case InvalidFormat = 'file.error.key_invalid_format';
    case NotFound = 'file.error.not_found';
    case OpenFailed = 'file.error.open_failed';
    case OperationFailed = 'file.error.storage_operation_failed';
}
