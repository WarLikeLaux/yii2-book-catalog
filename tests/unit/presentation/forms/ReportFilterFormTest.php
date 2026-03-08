<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\reports\forms\ReportFilterForm;
use PHPUnit\Framework\TestCase;

final class ReportFilterFormTest extends TestCase
{
    public function testAttributeLabelsReturnsYearLabel(): void
    {
        $form = new ReportFilterForm();

        $this->assertArrayHasKey('year', $form->attributeLabels());
    }
}
