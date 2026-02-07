<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\components\automapper;

use app\application\books\commands\CreateBookCommand;
use app\domain\values\BookYear;
use app\domain\values\Isbn;
use app\domain\values\StoredFileReference;
use app\infrastructure\components\automapper\ValueObjectStringPropertyTransformer;
use AutoMapper\Metadata\MapperMetadata;
use AutoMapper\Metadata\SourcePropertyMetadata;
use AutoMapper\Metadata\TargetPropertyMetadata;
use AutoMapper\Metadata\TypesMatching;
use Codeception\Test\Unit;
use Countable;
use DateTimeImmutable;
use ReflectionMethod;
use Stringable;

final class ValueObjectStringPropertyTransformerTest extends Unit
{
    private const COVER_PATH = '/files/cover.jpg';
    public function testSupportsCoverValueObject(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('storedCover');
        $target = new TargetPropertyMetadata('storedCover');
        $command = new class (new StoredFileReference(self::COVER_PATH)) {
            public function __construct(public StoredFileReference $storedCover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertTrue($transformer->supports($types, $source, $target, $metadata));
    }

    public function testTransformReturnsSameInstance(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('storedCover');
        $target = new TargetPropertyMetadata('storedCover');
        $command = new class (new StoredFileReference(self::COVER_PATH)) {
            public function __construct(public StoredFileReference $storedCover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertTrue($transformer->supports($types, $source, $target, $metadata));

        $value = new StoredFileReference(self::COVER_PATH);

        $this->assertSame($value, $transformer->transform($value, [], []));
    }

    public function testTransformCreatesValueObjectFromString(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('storedCover');
        $target = new TargetPropertyMetadata('storedCover');
        $command = new class (new StoredFileReference(self::COVER_PATH)) {
            public function __construct(public StoredFileReference $storedCover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertTrue($transformer->supports($types, $source, $target, $metadata));

        $result = $transformer->transform('/files/new-cover.jpg', [], []);

        $this->assertInstanceOf(StoredFileReference::class, $result);
        $this->assertSame('/files/new-cover.jpg', $result->getPath());
    }

    public function testSupportsReturnsFalseForNonValueObjectProperty(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('title');
        $target = new TargetPropertyMetadata('title');
        $metadata = new MapperMetadata('array', CreateBookCommand::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsFalseWhenTargetClassNotInDomainValues(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $command = new class (new DateTimeImmutable()) {
            public function __construct(public DateTimeImmutable $cover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsFalseWhenTargetReflectionMissing(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $metadata = new MapperMetadata('array', 'array', true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsFalseWhenTargetPropertyMissing(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('missing');
        $target = new TargetPropertyMetadata('missing');
        $command = new class (new StoredFileReference(self::COVER_PATH)) {
            public function __construct(public StoredFileReference $cover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsFalseWhenConstructorMissing(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $command = new class () {
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsFalseWhenParameterHasNoType(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $command = new class (null) {
            public function __construct(public $cover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsFalseWhenValueObjectConstructorHasMultipleParameters(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $command = new class (new BookYear(2001)) {
            public function __construct(public BookYear $cover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsFalseWhenUnionContainsDifferentClasses(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $command = new class (new StoredFileReference(self::COVER_PATH)) {
            public function __construct(public StoredFileReference|Isbn $cover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsTrueForNullableValueObjectUnion(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $command = new class () {
            public function __construct(public StoredFileReference|null $cover = null)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertTrue($transformer->supports($types, $source, $target, $metadata));
    }

    public function testSupportsReturnsTrueForUnionWithBuiltinAndValueObject(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $command = new class ('/files/cover.jpg') {
            public function __construct(public StoredFileReference|string $cover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertTrue($transformer->supports($types, $source, $target, $metadata));
    }

    public function testIsStringValueObjectReturnsFalseWhenClassMissing(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $method = new ReflectionMethod($transformer, 'isStringValueObject');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($transformer, 'app\\domain\\values\\MissingValueObject'));
    }

    public function testIsStringValueObjectReturnsFalseWhenConstructorMissing(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $method = new ReflectionMethod($transformer, 'isStringValueObject');
        $method->setAccessible(true);
        $valueObject = new class () {
        };

        $this->assertFalse($method->invoke($transformer, $valueObject::class));
    }

    public function testIsStringValueObjectReturnsFalseForUnionTypeParameter(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $method = new ReflectionMethod($transformer, 'isStringValueObject');
        $method->setAccessible(true);
        $valueObject = new class ('value') {
            public function __construct(public string|int $value)
            {
            }
        };

        $this->assertFalse($method->invoke($transformer, $valueObject::class));
    }

    public function testResolveClassNameReturnsNullForIntersectionType(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();
        $types = new TypesMatching();
        $source = new SourcePropertyMetadata('cover');
        $target = new TargetPropertyMetadata('cover');
        $value = new class () implements Countable, Stringable {
            public function count(): int
            {
                return 0;
            }

            public function __toString(): string
            {
                return 'value';
            }
        };
        $command = new class ($value) {
            public function __construct(public Countable&Stringable $cover)
            {
            }
        };
        $metadata = new MapperMetadata('array', $command::class, true);

        $this->assertFalse($transformer->supports($types, $source, $target, $metadata));
    }

    public function testTransformWithoutTargetClassReturnsValue(): void
    {
        $transformer = new ValueObjectStringPropertyTransformer();

        $this->assertSame('plain', $transformer->transform('plain', [], []));
    }
}
