<?php

declare(strict_types=1);

namespace tests\unit\presentation\reports\forms;

use app\presentation\reports\forms\ReportFilterForm;
use Codeception\Test\Unit;

final class ReportFilterFormTest extends Unit
{
    public function testRulesAndValidation(): void
    {
        $form = new ReportFilterForm();
        
        $form->year = 2023;
        $this->assertTrue($form->validate());
        
        $form->year = 1800;
        $this->assertFalse($form->validate());
        
        $form->year = 2200;
        $this->assertFalse($form->validate());
    }

    public function testLabels(): void
    {
        $form = new ReportFilterForm();
        $this->assertArrayHasKey('year', $form->attributeLabels());
    }
}