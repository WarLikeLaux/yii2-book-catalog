<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\reports\forms\ReportFilterForm;
use Codeception\Test\Unit;

final class ReportFilterFormTest extends Unit
{
    public function testAttributeLabelsReturnsYearLabel(): void
    {
        $form = new ReportFilterForm();

        $this->assertArrayHasKey('year', $form->attributeLabels());
    }
}
