<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\Integration;

use Http\Psr7Test\StreamIntegrationTest;
use Solluzi\Diactoros\Stream;
use Psr\Http\Message\StreamInterface;

final class StreamTest extends StreamIntegrationTest
{
    /** {@inheritDoc} */
    public function createStream($data): StreamInterface
    {
        if ($data instanceof StreamInterface) {
            return $data;
        }

        return new Stream($data);
    }
}
