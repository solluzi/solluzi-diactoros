<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\Integration;

use Http\Psr7Test\UriIntegrationTest;
use Solluzi\Diactoros\Uri;

final class UriTest extends UriIntegrationTest
{
    /** {@inheritDoc} */
    public function createUri($uri): Uri
    {
        return new Uri($uri);
    }
}
