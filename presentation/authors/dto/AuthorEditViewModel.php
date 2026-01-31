<?php

declare(strict_types=1);

namespace app\presentation\authors\dto;

use app\application\authors\queries\AuthorReadDto;
use app\presentation\authors\forms\AuthorForm;

final readonly class AuthorEditViewModel
{
    public function __construct(
        public AuthorForm $form,
        public ?AuthorReadDto $author = null,
    ) {
    }
}
