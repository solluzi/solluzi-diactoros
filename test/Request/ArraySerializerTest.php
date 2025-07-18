<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros\Request;

use Solluzi\Diactoros\Request;
use Solluzi\Diactoros\Request\ArraySerializer;
use Solluzi\Diactoros\Stream;
use Solluzi\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class ArraySerializerTest extends TestCase
{
    public function testSerializeToArray(): void
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);

        $message = ArraySerializer::toArray($request);

        $this->assertSame([
            'method'           => 'POST',
            'request_target'   => '/foo/bar?baz=bat',
            'uri'              => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers'          => [
                'Host'      => [
                    'example.com',
                ],
                'Accept'    => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat',
                ],
            ],
            'body'             => '{"test":"value"}',
        ], $message);
    }

    public function testDeserializeFromArray(): void
    {
        $serializedRequest = [
            'method'           => 'POST',
            'request_target'   => '/foo/bar?baz=bat',
            'uri'              => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers'          => [
                'Host'      => [
                    'example.com',
                ],
                'Accept'    => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat',
                ],
            ],
            'body'             => '{"test":"value"}',
        ];

        $message = ArraySerializer::fromArray($serializedRequest);

        $stream = new Stream('php://memory', 'wb+');
        $stream->write('{"test":"value"}');

        $request = (new Request())
            ->withMethod('POST')
            ->withUri(new Uri('http://example.com/foo/bar?baz=bat'))
            ->withAddedHeader('Accept', 'application/json')
            ->withAddedHeader('X-Foo-Bar', 'Baz')
            ->withAddedHeader('X-Foo-Bar', 'Bat')
            ->withBody($stream);

        $this->assertSame(Request\Serializer::toString($request), Request\Serializer::toString($message));
    }

    public function testMissingBodyParamInSerializedRequestThrowsException(): void
    {
        $serializedRequest = [
            'method'           => 'POST',
            'request_target'   => '/foo/bar?baz=bat',
            'uri'              => 'http://example.com/foo/bar?baz=bat',
            'protocol_version' => '1.1',
            'headers'          => [
                'Host'      => [
                    'example.com',
                ],
                'Accept'    => [
                    'application/json',
                ],
                'X-Foo-Bar' => [
                    'Baz',
                    'Bat',
                ],
            ],
        ];

        $this->expectException(UnexpectedValueException::class);

        ArraySerializer::fromArray($serializedRequest);
    }
}
