<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\Integration;

use Http\Psr7Test\ResponseIntegrationTest;
use Solluzi\Diactoros\Response;

final class ResponseTest extends ResponseIntegrationTest
{
    public function createSubject(): Response
    {
        return new Response();
    }
}
