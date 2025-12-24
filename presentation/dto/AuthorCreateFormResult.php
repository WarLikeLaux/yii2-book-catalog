<?php

declare(strict_types=1);

namespace app\presentation\dto;

use app\presentation\forms\AuthorForm;

final class AuthorCreateFormResult
{
    public function __construct(
        public readonly AuthorForm $form,
        public readonly array $viewData,
        public readonly bool $success,
        public readonly array|null $redirectRoute = null
    ) {
    }
}
