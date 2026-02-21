<?php

declare(strict_types=1);

namespace tests\unit\infrastructure\services\sms;

use app\infrastructure\services\sms\PhoneMasker;
use Codeception\Test\Unit;

final class PhoneMaskerTest extends Unit
{
    public function testMaskShortPhoneFullyMasked(): void
    {
        $this->assertSame('****', PhoneMasker::mask('1234'));
    }

    public function testMaskVeryShortPhone(): void
    {
        $this->assertSame('*', PhoneMasker::mask('1'));
        $this->assertSame('**', PhoneMasker::mask('12'));
    }

    public function testMaskStandardPhone(): void
    {
        $this->assertSame('+7********67', PhoneMasker::mask('+79991234567'));
    }

    public function testMaskE164Phone(): void
    {
        $this->assertSame('79*******90', PhoneMasker::mask('79001234590'));
    }
}
