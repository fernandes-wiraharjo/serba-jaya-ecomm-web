<?php

namespace Tests\Unit;

use App\Support\IndonesianPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IndonesianPhoneTest extends TestCase
{
    #[DataProvider('phones')]
    public function test_normalize_stores_an_indonesian_mobile_number(string $input, string $expected): void
    {
        $this->assertSame($expected, IndonesianPhone::normalize($input));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function phones(): array
    {
        return [
            'local leading zero' => ['081234567890', '6281234567890'],
            'spaces and dashes' => ['0812-3456-7890', '6281234567890'],
            'plus country code' => ['+62 812 3456 7890', '6281234567890'],
            'already normalized' => ['6281234567890', '6281234567890'],
            'missing prefix' => ['81234567890', '6281234567890'],
        ];
    }
}
