<?php

declare(strict_types=1);

namespace app\tests\unit\infrastructure\components\hydrator;

use app\infrastructure\components\hydrator\ActiveRecordHydrator;
use Codeception\Test\Unit;
use yii\db\ActiveRecord;

final class ActiveRecordHydratorTest extends Unit
{
    private ActiveRecordHydrator $hydrator;

    protected function _before(): void
    {
        $this->hydrator = new ActiveRecordHydrator();
    }

    public function testDirectCopyWithIntegerKey(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public string $title = 'Test Title';
        };

        $this->hydrator->hydrate($ar, $source, ['title']);

        $this->assertSame('Test Title', $ar->title);
    }

    public function testAliasMapping(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public string $name = 'Source Name';
        };

        $this->hydrator->hydrate($ar, $source, ['title' => 'name']);

        $this->assertSame('Source Name', $ar->title);
    }

    public function testSmartUnboxingWithPublicValueProperty(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public object $year;

            public function __construct()
            {
                $this->year = new class {
                    public int $value = 2024;
                };
            }
        };

        $this->hydrator->hydrate($ar, $source, ['year']);

        $this->assertSame(2024, $ar->year);
    }

    public function testSmartUnboxingWithBackedEnum(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public TestStatus $status;

            public function __construct()
            {
                $this->status = TestStatus::Active;
            }
        };

        $this->hydrator->hydrate($ar, $source, ['status']);

        $this->assertSame(1, $ar->status);
    }

    public function testClosureTransformation(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public bool $published = true;
        };

        $this->hydrator->hydrate($ar, $source, [
            'is_active' => static fn(object $e): int => $e->published ? 1 : 0,
        ]);

        $this->assertSame(1, $ar->is_active);
    }

    public function testClosureTransformationWithFalseValue(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public bool $published = false;
        };

        $this->hydrator->hydrate($ar, $source, [
            'is_active' => static fn(object $e): int => $e->published ? 1 : 0,
        ]);

        $this->assertSame(0, $ar->is_active);
    }

    public function testNullableValueDirectCopy(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public ?string $description = null;
        };

        $this->hydrator->hydrate($ar, $source, ['description']);

        $this->assertNull($ar->description);
    }

    public function testNullableValueObjectUnboxing(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public ?object $coverImage = null;
        };

        $this->hydrator->hydrate($ar, $source, [
            'cover_url' => static fn(object $e): ?string => $e->coverImage?->getPath(),
        ]);

        $this->assertNull($ar->cover_url);
    }

    public function testMultipleFieldsMapping(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public string $title = 'Book Title';
            public ?string $description = 'Book Description';
            public object $year;

            public function __construct()
            {
                $this->year = new class {
                    public int $value = 2025;
                };
            }
        };

        $this->hydrator->hydrate($ar, $source, [
            'title',
            'description',
            'year',
        ]);

        $this->assertSame('Book Title', $ar->title);
        $this->assertSame('Book Description', $ar->description);
        $this->assertSame(2025, $ar->year);
    }

    public function testMixedMappingStrategies(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public string $title = 'Title';
            public string $bookName = 'Alternative Name';
            public object $year;
            public bool $published = true;

            public function __construct()
            {
                $this->year = new class {
                    public int $value = 2024;
                };
            }
        };

        $this->hydrator->hydrate($ar, $source, [
            'title',
            'description' => 'bookName',
            'year',
            'is_active' => static fn(object $e): int => $e->published ? 1 : 0,
        ]);

        $this->assertSame('Title', $ar->title);
        $this->assertSame('Alternative Name', $ar->description);
        $this->assertSame(2024, $ar->year);
        $this->assertSame(1, $ar->is_active);
    }

    public function testSmartUnboxingWithStringBackedEnum(): void
    {
        $ar = $this->createMockActiveRecord();
        $source = new class {
            public TestStringStatus $status;

            public function __construct()
            {
                $this->status = TestStringStatus::Published;
            }
        };

        $this->hydrator->hydrate($ar, $source, ['status']);

        $this->assertSame(TestStringStatus::Published->value, $ar->status);
    }

    public function testObjectWithoutValuePropertyIsNotUnboxed(): void
    {
        $ar = $this->createMockActiveRecord();
        $vo = new class {
            public string $path = '/uploads/cover.jpg';
        };
        $source = new class ($vo) {
            public function __construct(
                public object $data,
            ) {
            }
        };

        $this->hydrator->hydrate($ar, $source, ['data']);

        $this->assertSame($vo, $ar->data);
    }

    public function testObjectWithPrivateValuePropertyIsNotUnboxed(): void
    {
        $ar = $this->createMockActiveRecord();
        $vo = new class {
            private int $value = 42;
        };
        $source = new class ($vo) {
            public function __construct(
                public object $data,
            ) {
            }
        };

        $this->hydrator->hydrate($ar, $source, ['data']);

        $this->assertSame($vo, $ar->data);
    }

    /**
     * @return ActiveRecord&object{title: string, description: ?string, year: int, status: int|string, is_active: int, cover_url: ?string, data: mixed}
     */
    private function createMockActiveRecord(): ActiveRecord
    {
        return new class extends ActiveRecord {
            public string $title = '';
            public ?string $description = null;
            public int $year = 0;

            /** @var int|string */
            public int|string $status = 0;
            public int $is_active = 0;
            public ?string $cover_url = null;
            public mixed $data = null;
        };
    }
}
