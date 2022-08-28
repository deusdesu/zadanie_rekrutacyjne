<?php

namespace App\Tests;

use App\Entity\UserCode;
use PHPUnit\Framework\TestCase;

class CodeTest extends TestCase
{
    /**
     * @dataProvider kody
     */
    public function testNumber(string $kod): void
    {
        $this->assertIsString($kod);
        $this->assertIsObject(UserCode::class,$kod);
        $this->assertNotEmpty($kod);

    }
    private function kody(){
        return [
            ['000000049617410'],
            ['000000168255001'],
            ['00000512752451M'],
            ['000032225342500'],
        ];
    }
}
