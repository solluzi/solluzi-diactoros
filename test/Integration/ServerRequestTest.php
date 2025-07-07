<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\Integration;

use Http\Psr7Test\ServerRequestIntegrationTest;
use Solluzi\Diactoros\ServerRequest;

final class ServerRequestTest extends ServerRequestIntegrationTest
{
    public function createSubject(): ServerRequest
    {
        return new ServerRequest($_SERVER);
    }
}
