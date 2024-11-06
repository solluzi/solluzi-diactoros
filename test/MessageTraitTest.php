<?php

declare(strict_types=1);

namespace LaminasTest\Diactoros;

use InvalidArgumentException;
use Laminas\Diactoros\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

use function count;
use function trim;

class MessageTraitTest extends TestCase
{
    /** @var MessageInterface */
    protected $message;

    protected function setUp(): void
    {
        $this->message = new Request(null, null, $this->createMock(StreamInterface::class));
    }

    public function testProtocolHasAcceptableDefault(): void
    {
        $this->assertSame('1.1', $this->message->getProtocolVersion());
    }

    public function testProtocolMutatorReturnsCloneWithChanges(): void
    {
        $message = $this->message->withProtocolVersion('1.0');
        $this->assertNotSame($this->message, $message);
        $this->assertSame('1.0', $message->getProtocolVersion());
    }

    /** @return non-empty-array<non-empty-string, array{0: string}> */
    public static function invalidProtocolVersionProvider(): array
    {
        return [
            '1-without-minor'      => ['1'],
            '1-with-invalid-minor' => ['1.2'],
            '1-with-hotfix'        => ['1.1.1'],
        ];
    }

    #[DataProvider('invalidProtocolVersionProvider')]
    public function testWithProtocolVersionRaisesExceptionForInvalidVersion(string $version): void
    {
        $request = new Request();
        $this->expectException(InvalidArgumentException::class);
        $request->withProtocolVersion($version);
    }

    /** @return non-empty-array<array{non-empty-string}> */
    public static function validProtocolVersionProvider(): array
    {
        return [
            '1.0' => ['1.0'],
            '1.1' => ['1.1'],
            '2'   => ['2'],
            '2.0' => ['2.0'],
        ];
    }

    #[DataProvider('validProtocolVersionProvider')]
    public function testWithProtocolVersionDoesntRaiseExceptionForValidVersion(string $version): void
    {
        $request = (new Request())->withProtocolVersion($version);
        $this->assertEquals($version, $request->getProtocolVersion());
    }

    public function testUsesStreamProvidedInConstructorAsBody(): void
    {
        $stream  = $this->createMock(StreamInterface::class);
        $message = new Request(null, null, $stream);
        $this->assertSame($stream, $message->getBody());
    }

    public function testBodyMutatorReturnsCloneWithChanges(): void
    {
        $stream  = $this->createMock(StreamInterface::class);
        $message = $this->message->withBody($stream);
        $this->assertNotSame($this->message, $message);
        $this->assertSame($stream, $message->getBody());
    }

    public function testGetHeaderReturnsHeaderValueAsArray(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['Foo', 'Bar'], $message->getHeader('X-Foo'));
    }

    public function testGetHeaderLineReturnsHeaderValueAsCommaConcatenatedString(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame('Foo,Bar', $message->getHeaderLine('X-Foo'));
    }

    public function testGetHeadersKeepsHeaderCaseSensitivity(): void
    {
        $message = $this->message->withHeader('X-Foo', ['Foo', 'Bar']);
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    public function testGetHeadersReturnsCaseWithWhichHeaderFirstRegistered(): void
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar');
        $this->assertNotSame($this->message, $message);
        $this->assertSame(['X-Foo' => ['Foo', 'Bar']], $message->getHeaders());
    }

    public function testHasHeaderReturnsFalseIfHeaderIsNotPresent(): void
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
    }

    public function testHasHeaderReturnsTrueIfHeaderIsPresent(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('X-Foo'));
    }

    public function testAddHeaderAppendsToExistingHeader(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $message2 = $message->withAddedHeader('X-Foo', 'Bar');
        $this->assertNotSame($message, $message2);
        $this->assertSame('Foo,Bar', $message2->getHeaderLine('X-Foo'));
    }

    public function testCanRemoveHeaders(): void
    {
        $message = $this->message->withHeader('X-Foo', 'Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));
        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));
    }

    public function testHeaderRemovalIsCaseInsensitive(): void
    {
        $message = $this->message
            ->withHeader('X-Foo', 'Foo')
            ->withAddedHeader('x-foo', 'Bar')
            ->withAddedHeader('X-FOO', 'Baz');
        $this->assertNotSame($this->message, $message);
        $this->assertTrue($message->hasHeader('x-foo'));

        $message2 = $message->withoutHeader('x-foo');
        $this->assertNotSame($this->message, $message2);
        $this->assertNotSame($message, $message2);
        $this->assertFalse($message2->hasHeader('X-Foo'));

        $headers = $message2->getHeaders();
        $this->assertSame(0, count($headers));
    }

    /** @return non-empty-array<non-empty-string, array{mixed}> */
    public static function invalidGeneralHeaderValues(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'array'  => [['foo' => ['bar']]],
            'object' => [(object) ['foo' => 'bar']],
        ];
    }

    #[DataProvider('invalidGeneralHeaderValues')]
    public function testWithHeaderRaisesExceptionForInvalidNestedHeaderValue(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header value');

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->message->withHeader('X-Foo', [$value]);
    }

    /** @return non-empty-array<non-empty-string, array{mixed}> */
    public static function invalidHeaderValues(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'object' => [(object) ['foo' => 'bar']],
        ];
    }

    #[DataProvider('invalidHeaderValues')]
    public function testWithHeaderRaisesExceptionForInvalidValueType(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid header value');

        /** @psalm-suppress MixedArgument */
        $this->message->withHeader('X-Foo', $value);
    }

    public function testWithHeaderReplacesDifferentCapitalization(): void
    {
        $this->message = $this->message->withHeader('X-Foo', ['foo']);
        $new           = $this->message->withHeader('X-foo', ['bar']);
        $this->assertSame(['bar'], $new->getHeader('x-foo'));
        $this->assertSame(['X-foo' => ['bar']], $new->getHeaders());
    }

    #[DataProvider('invalidGeneralHeaderValues')]
    public function testWithAddedHeaderRaisesExceptionForNonStringNonArrayValue(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must be a string');

        $this->message->withAddedHeader('X-Foo', $value);
    }

    public function testWithoutHeaderDoesNothingIfHeaderDoesNotExist(): void
    {
        $this->assertFalse($this->message->hasHeader('X-Foo'));
        $message = $this->message->withoutHeader('X-Foo');
        $this->assertNotSame($this->message, $message);
        $this->assertFalse($message->hasHeader('X-Foo'));
    }

    public function testHeadersInitialization(): void
    {
        $headers = ['X-Foo' => ['bar']];
        $message = new Request(null, null, 'php://temp', $headers);
        $this->assertSame($headers, $message->getHeaders());
    }

    public function testGetHeaderReturnsAnEmptyArrayWhenHeaderDoesNotExist(): void
    {
        $this->assertSame([], $this->message->getHeader('X-Foo-Bar'));
    }

    public function testGetHeaderLineReturnsEmptyStringWhenHeaderDoesNotExist(): void
    {
        $this->assertEmpty($this->message->getHeaderLine('X-Foo-Bar'));
    }

    /** @return non-empty-array<non-empty-string, array{non-empty-string, non-empty-string|array{non-empty-string}}> */
    public static function headersWithInjectionVectors(): array
    {
        return [
            'name-with-cr'           => ["X-Foo\r-Bar", 'value'],
            'name-with-lf'           => ["X-Foo\n-Bar", 'value'],
            'name-with-crlf'         => ["X-Foo\r\n-Bar", 'value'],
            'name-with-2crlf'        => ["X-Foo\r\n\r\n-Bar", 'value'],
            'name-with-trailing-lf'  => ["X-Foo-Bar\n", 'value'],
            'name-with-leading-lf'   => ["\nX-Foo-Bar", 'value'],
            'value-with-cr'          => ['X-Foo-Bar', "value\rinjection"],
            'value-with-lf'          => ['X-Foo-Bar', "value\ninjection"],
            'value-with-crlf'        => ['X-Foo-Bar', "value\r\ninjection"],
            'value-with-2crlf'       => ['X-Foo-Bar', "value\r\n\r\ninjection"],
            'array-value-with-cr'    => ['X-Foo-Bar', ["value\rinjection"]],
            'array-value-with-lf'    => ['X-Foo-Bar', ["value\ninjection"]],
            'array-value-with-crlf'  => ['X-Foo-Bar', ["value\r\ninjection"]],
            'array-value-with-2crlf' => ['X-Foo-Bar', ["value\r\n\r\ninjection"]],
            'value-with-trailing-lf' => ['X-Foo-Bar', "value\n"],
            'value-with-leading-lf'  => ['X-Foo-Bar', "\nvalue"],
        ];
    }

    /**
     * @param string               $name
     * @param string|array{string} $value
     */
    #[DataProvider('headersWithInjectionVectors')]
    #[Group('ZF2015-04')]
    public function testDoesNotAllowCRLFInjectionWhenCallingWithHeader($name, $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->message->withHeader($name, $value);
    }

    /**
     * @param string               $name
     * @param string|array{string} $value
     */
    #[DataProvider('headersWithInjectionVectors')]
    #[Group('ZF2015-04')]
    public function testDoesNotAllowCRLFInjectionWhenCallingWithAddedHeader($name, $value): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->message->withAddedHeader($name, $value);
    }

    public function testWithHeaderAllowsHeaderContinuations(): void
    {
        $message = $this->message->withHeader('X-Foo-Bar', "value,\r\n second value");
        $this->assertSame("value, second value", $message->getHeaderLine('X-Foo-Bar'));
    }

    public function testWithAddedHeaderAllowsHeaderContinuations(): void
    {
        $message = $this->message->withAddedHeader('X-Foo-Bar', "value,\r\n second value");
        $this->assertSame("value, second value", $message->getHeaderLine('X-Foo-Bar'));
    }

    /** @return non-empty-array<non-empty-string, array{non-empty-string}> */
    public static function headersWithWhitespace(): array
    {
        return [
            'no'       => ["Baz"],
            'leading'  => [" Baz"],
            'trailing' => ["Baz "],
            'both'     => [" Baz "],
            'mixed'    => [" \t Baz\t \t"],
        ];
    }

    #[DataProvider('headersWithWhitespace')]
    public function testWithHeaderTrimsWhitespace(string $value): void
    {
        $message = $this->message->withHeader('X-Foo-Bar', $value);
        $this->assertSame(trim($value, "\t "), $message->getHeaderLine('X-Foo-Bar'));
    }

    /** @return non-empty-array<non-empty-string, array{non-empty-string}> */
    public static function headersWithContinuation(): array
    {
        return [
            'space' => ["foo\r\n bar"],
            'tab'   => ["foo\r\n\tbar"],
        ];
    }

    #[DataProvider('headersWithContinuation')]
    public function testWithHeaderNormalizesContinuationToNotContainNewlines(string $value): void
    {
        $message = $this->message->withHeader('X-Foo-Bar', $value);
        // Newlines must no longer appear.
        $this->assertStringNotContainsString("\r", $message->getHeaderLine('X-Foo-Bar'));
        $this->assertStringNotContainsString("\n", $message->getHeaderLine('X-Foo-Bar'));
        // But there must be at least one space.
        $this->assertStringContainsString(' ', $message->getHeaderLine('X-Foo-Bar'));
    }

    /** @return non-empty-array<non-empty-string, array{int|float}> */
    public static function numericHeaderValuesProvider(): array
    {
        return [
            'integer' => [123],
            'float'   => [12.3],
        ];
    }

    /**
     * @psalm-suppress InvalidArgument this test
     *     explicitly verifies that pre-type-declaration implicit type
     *     conversion semantics still apply, for BC Compliance
     */
    #[DataProvider('numericHeaderValuesProvider')]
    #[Group('99')]
    public function testWithHeaderShouldAllowIntegersAndFloats(float $value): void
    {
        $message = $this->message
            ->withHeader('X-Test-Array', [$value])
            ->withHeader('X-Test-Scalar', $value);

        $this->assertSame([
            'X-Test-Array'  => [(string) $value],
            'X-Test-Scalar' => [(string) $value],
        ], $message->getHeaders());
    }

    /** @return non-empty-array<non-empty-string, array{mixed}> */
    public static function invalidHeaderValueTypes(): array
    {
        return [
            'null'   => [null],
            'true'   => [true],
            'false'  => [false],
            'object' => [(object) ['header' => ['foo', 'bar']]],
        ];
    }

    /** @return non-empty-array<non-empty-string, array{mixed}> */
    public static function invalidArrayHeaderValues(): array
    {
        $values          = self::invalidHeaderValueTypes();
        $values['array'] = [['INVALID']];
        return $values;
    }

    #[DataProvider('invalidArrayHeaderValues')]
    #[Group('99')]
    public function testWithHeaderShouldRaiseExceptionForInvalidHeaderValuesInArrays(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('header value type');

        /** @psalm-suppress MixedArgumentTypeCoercion */
        $this->message->withHeader('X-Test-Array', [$value]);
    }

    #[DataProvider('invalidHeaderValueTypes')]
    #[Group('99')]
    public function testWithHeaderShouldRaiseExceptionForInvalidHeaderScalarValues(mixed $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('header value type');

        /** @psalm-suppress MixedArgument */
        $this->message->withHeader('X-Test-Scalar', $value);
    }
}
