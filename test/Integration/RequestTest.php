<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\Integration;

use Http\Psr7Test\RequestIntegrationTest;
use Solluzi\Diactoros\Request;

final class RequestTest extends RequestIntegrationTest
{
    public function createSubject(): Request
    {
        return new Request('/', 'GET');
    }
}
