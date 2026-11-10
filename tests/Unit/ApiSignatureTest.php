<?php

namespace Tests\Unit;

use App\Support\ApiSignature;
use PHPUnit\Framework\TestCase;

class ApiSignatureTest extends TestCase
{
    public function test_empty_payload_hashes_nonce_and_secret(): void
    {
        $signature = new ApiSignature;

        $this->assertSame(
            hash('sha256', '1710000000sk_dev_secret'),
            $signature->hash([], '1710000000', 'sk_dev_secret'),
        );
    }

    public function test_payload_values_are_sorted_and_nulls_are_skipped(): void
    {
        $signature = new ApiSignature;

        $this->assertSame(
            hash('sha256', 'truebetazebra1710000000sk_dev_secret'),
            $signature->hash([
                'zebra' => 'zebra',
                'alpha' => null,
                'active' => true,
                'beta' => 'beta',
            ], '1710000000', 'sk_dev_secret'),
        );
    }
}
