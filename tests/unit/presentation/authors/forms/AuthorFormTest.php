<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\forms;

use app\application\ports\AuthorQueryServiceInterface;
use app\presentation\authors\forms\AuthorForm;
use Codeception\Test\Unit;

final class AuthorFormTest extends Unit
{
    public function testValidateFioUniqueIgnoresNonStringFio(): void
    {
        $queryService = $this->createMock(AuthorQueryServiceInterface::class);
        $queryService->expects($this->never())->method('existsByFio');

        $form = new AuthorForm($queryService);
        $form->fio = 123;

        $form->validateFioUnique('fio');

        $this->assertFalse($form->hasErrors('fio'));
    }
}
