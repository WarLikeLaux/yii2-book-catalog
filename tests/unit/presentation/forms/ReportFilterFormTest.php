<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\forms\ReportFilterForm;
use Codeception\Test\Unit;

final class ReportFilterFormTest extends Unit
{
    public function testFormName(): void
    {
        $form = new ReportFilterForm();
        $this->assertSame('', $form->formName());
    }

    public function testRules(): void
    {
        $form = new ReportFilterForm();
        $rules = $form->rules();
        
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    public function testAttributeLabels(): void
    {
        $form = new ReportFilterForm();
        $labels = $form->attributeLabels();
        
        $this->assertArrayHasKey('year', $labels);
    }

    public function testValidYearPasses(): void
    {
        $form = new ReportFilterForm();
        $form->year = 2020;
        
        $this->assertTrue($form->validate());
    }

    public function testNullYearPasses(): void
    {
        $form = new ReportFilterForm();
        $form->year = null;
        
        $this->assertTrue($form->validate());
    }
}
