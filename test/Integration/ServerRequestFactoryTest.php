<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\Integration;

use Http\Psr7Test\ServerRequestIntegrationTest;
use Solluzi\Diactoros\ServerRequestFactory;
use Psr\Http\Message\ServerRequestInterface;

final class ServerRequestFactoryTest extends ServerRequestIntegrationTest
{
    public function createSubject(): ServerRequestInterface
    {
        return (new ServerRequestFactory())->createServerRequest('GET', '/', $_SERVER);
    }
}
