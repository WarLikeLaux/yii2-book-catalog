<?php

declare(strict_types=1);

namespace tests\unit\presentation\forms;

use app\presentation\forms\BookForm;
use Codeception\Test\Unit;

final class BookFormTest extends Unit
{
    public function testRules(): void
    {
        $form = new BookForm();
        $rules = $form->rules();
        
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
    }

    public function testAttributeLabels(): void
    {
        $form = new BookForm();
        $labels = $form->attributeLabels();
        
        $this->assertArrayHasKey('title', $labels);
        $this->assertArrayHasKey('year', $labels);
        $this->assertArrayHasKey('isbn', $labels);
    }

    public function testDefaultValues(): void
    {
        $form = new BookForm();
        
        $this->assertSame('', $form->title);
        $this->assertNull($form->year);
        $this->assertSame('', $form->isbn);
        $this->assertSame([], $form->authorIds);
    }
}
