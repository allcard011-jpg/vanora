<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_nomor_valid_dinormalisasi_ke_format_62(): void
    {
        $this->assertSame('6285124048406', Phone::normalize('085124048406'));
        $this->assertSame('6285124048406', Phone::normalize('+62 851-2404-8406'));
        $this->assertSame('6285124048406', Phone::normalize('6285124048406'));
        $this->assertSame('6285124048406', Phone::normalize('85124048406'));
    }

    public function test_nomor_tidak_valid_ditolak(): void
    {
        $this->assertNull(Phone::normalize(''));
        $this->assertNull(Phone::normalize('abc'));
        $this->assertNull(Phone::normalize('0851'));
        $this->assertNull(Phone::normalize('0211234567'));
        $this->assertNull(Phone::normalize('08512404840612345678'));
    }
}
