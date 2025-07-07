<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\ServerRequestFilter;

use Solluzi\Diactoros\ServerRequest;
use Solluzi\Diactoros\ServerRequestFilter\DoNotFilter;
use PHPUnit\Framework\TestCase;

class DoNotFilterTest extends TestCase
{
    public function testReturnsSameInstanceItWasProvided(): void
    {
        $request = new ServerRequest();
        $filter  = new DoNotFilter();

        $this->assertSame($request, $filter($request));
    }
}
