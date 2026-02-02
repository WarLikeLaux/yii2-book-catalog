<?php

declare(strict_types=1);

namespace tests\unit\presentation\common\handlers;

use app\application\common\exceptions\ApplicationException;
use app\presentation\common\handlers\ErrorMappingTrait;
use Codeception\Test\Unit;
use yii\base\Model;

final class ErrorMappingTraitTest extends Unit
{
    private ErrorMappingTraitImpl $handler;

    protected function _before(): void
    {
        $this->handler = new ErrorMappingTraitImpl();
    }

    public function testGetErrorFieldMapReturnsEmptyArray(): void
    {
        $result = $this->handler->callGetErrorFieldMap();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testAddFormErrorWithMappedField(): void
    {
        $form = $this->createMock(Model::class);
        $form->method('attributes')->willReturn(['title', 'description']);

        $exception = new ApplicationException('test.error');

        $form->expects($this->once())
            ->method('addError')
            ->with('title', $this->anything());

        $this->handler->callAddFormError($form, $exception);
    }

    public function testAddFormErrorWithUnmappedFieldUsesFirstPreferred(): void
    {
        $form = $this->createMock(Model::class);
        $form->method('attributes')->willReturn(['name', 'description']);

        $exception = new ApplicationException('unmapped.error');

        $form->expects($this->once())
            ->method('addError')
            ->with('name', $this->anything());

        $this->handler->callAddFormError($form, $exception);
    }

    public function testAddFormErrorFallsBackToFirstAttribute(): void
    {
        $form = $this->createMock(Model::class);
        $form->method('attributes')->willReturn(['id', 'version', 'customField']);

        $exception = new ApplicationException('unmapped.error');

        $form->expects($this->once())
            ->method('addError')
            ->with('customField', $this->anything());

        $this->handler->callAddFormError($form, $exception);
    }

    public function testAddFormErrorWithEmptyFieldWhenNoAttributes(): void
    {
        $form = $this->createMock(Model::class);
        $form->method('attributes')->willReturn([]);

        $exception = new ApplicationException('test.error');

        $form->expects($this->once())
            ->method('addError')
            ->with('', $this->anything());

        $this->handler->callAddFormError($form, $exception);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
final class ErrorMappingTraitImpl
{
    use ErrorMappingTrait;

    public function callGetErrorFieldMap(): array
    {
        return $this->getErrorFieldMap();
    }

    public function callAddFormError(Model $form, ApplicationException $e): void
    {
        $this->addFormError($form, $e);
    }
}
