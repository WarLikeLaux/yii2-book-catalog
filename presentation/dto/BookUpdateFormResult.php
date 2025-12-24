<?php

declare(strict_types=1);

namespace app\presentation\dto;

use app\presentation\forms\BookForm;

final class BookUpdateFormResult
{
    public function __construct(
        public readonly BookForm $form,
        public readonly array $viewData,
        public readonly bool $success,
        public readonly array|null $redirectRoute = null,
        public readonly array|null $ajaxValidation = null
    ) {
    }
}
