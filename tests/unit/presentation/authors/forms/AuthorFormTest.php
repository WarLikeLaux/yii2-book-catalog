<?php

declare(strict_types=1);

namespace tests\unit\presentation\authors\forms;

use app\presentation\authors\forms\AuthorForm;
use PHPUnit\Framework\TestCase;

final class AuthorFormTest extends TestCase
{
    public function testValidateFioRequired(): void
    {
        $form = new AuthorForm();
        $form->fio = '';

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('fio'));
    }

    public function testValidateFioMaxLength(): void
    {
        $form = new AuthorForm();
        $form->fio = str_repeat('a', 256);

        $this->assertFalse($form->validate());
        $this->assertTrue($form->hasErrors('fio'));
    }

    public function testValidateFioSuccess(): void
    {
        $form = new AuthorForm();
        $form->fio = 'Иванов Иван Иванович';

        $this->assertTrue($form->validate());
    }
}
