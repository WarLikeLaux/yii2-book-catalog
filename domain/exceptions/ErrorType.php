<?php

declare(strict_types=1);

namespace app\domain\exceptions;

enum ErrorType
{
    case NotFound;
    case AlreadyExists;
    case OperationFailed;
    case BusinessRule;
}
