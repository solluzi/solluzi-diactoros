<?php

declare(strict_types=1);

namespace SolluziTest\Diactoros;

use Solluzi\Diactoros\CallbackStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(CallbackStream::class)]
final class CallbackStreamTest extends TestCase
{
    public function testToString(): void
    {
        $stream = new CallbackStream(static fn(): string => 'foobarbaz');

        $ret = $stream->__toString();
        $this->assertSame('foobarbaz', $ret);
    }

    public function testClose(): void
    {
        $stream = new CallbackStream(static function (): void {
        });

        $stream->close();

        $callback = $stream->detach();

        $this->assertNull($callback);
    }

    public function testDetach(): void
    {
        $callback = static function (): void {
        };
        $stream   = new CallbackStream($callback);
        $ret      = $stream->detach();
        $this->assertSame($callback, $ret);
    }

    public function testEof(): void
    {
        $stream = new CallbackStream(static function (): void {
        });
        $ret    = $stream->eof();
        $this->assertFalse($ret);

        $stream->getContents();
        $ret = $stream->eof();
        $this->assertTrue($ret);
    }

    public function testGetSize(): void
    {
        $stream = new CallbackStream(static function (): void {
        });
        $ret    = $stream->getSize();
        $this->assertNull($ret);
    }

    public function testTell(): void
    {
        $stream = new CallbackStream(static function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->tell();
    }

    public function testIsSeekable(): void
    {
        $stream = new CallbackStream(static function (): void {
        });
        $ret    = $stream->isSeekable();
        $this->assertFalse($ret);
    }

    public function testIsWritable(): void
    {
        $stream = new CallbackStream(static function (): void {
        });
        $ret    = $stream->isWritable();
        $this->assertFalse($ret);
    }

    public function testIsReadable(): void
    {
        $stream = new CallbackStream(static function (): void {
        });
        $ret    = $stream->isReadable();
        $this->assertFalse($ret);
    }

    public function testSeek(): void
    {
        $stream = new CallbackStream(static function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->seek(0);
    }

    public function testRewind(): void
    {
        $stream = new CallbackStream(static function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->rewind();
    }

    public function testWrite(): void
    {
        $stream = new CallbackStream(static function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->write('foobarbaz');
    }

    public function testRead(): void
    {
        $stream = new CallbackStream(static function (): void {
        });

        $this->expectException(RuntimeException::class);

        $stream->read(3);
    }

    public function testGetContents(): void
    {
        $stream = new CallbackStream(static fn(): string => 'foobarbaz');

        $ret = $stream->getContents();
        $this->assertSame('foobarbaz', $ret);
    }

    public function testGetMetadata(): void
    {
        $stream = new CallbackStream(static function (): void {
        });

        $ret = $stream->getMetadata('stream_type');
        $this->assertSame('callback', $ret);

        $ret = $stream->getMetadata('seekable');
        $this->assertFalse($ret);

        $ret = $stream->getMetadata('eof');
        $this->assertFalse($ret);

        $all = $stream->getMetadata();
        $this->assertSame([
            'eof'         => false,
            'stream_type' => 'callback',
            'seekable'    => false,
        ], $all);

        $notExists = $stream->getMetadata('boo');
        $this->assertNull($notExists);
    }

    /**
     * @link \SolluziTest\Diactoros\TestAsset\CallbacksForCallbackStream::sampleStaticCallback()
     * @link \SolluziTest\Diactoros\TestAsset\CallbacksForCallbackStream::sampleCallback()
     *
     * @return non-empty-array<non-empty-string, array{callable(): string, non-empty-string}>
     */
    public static function phpCallbacksForStreams(): array
    {
        $class = TestAsset\CallbacksForCallbackStream::class;

        // phpcs:disable Generic.Files.LineLength
        return [
            'instance-method' => [[new TestAsset\CallbacksForCallbackStream(), 'sampleCallback'], $class . '::sampleCallback'],
            'static-method'   => [[$class, 'sampleStaticCallback'], $class . '::sampleStaticCallback'],
        ];
        // phpcs:enable Generic.Files.LineLength
    }

    /**
     * @param callable(): string $callback
     * @param non-empty-string $expected
     */
    #[DataProvider('phpCallbacksForStreams')]
    public function testAllowsArbitraryPhpCallbacks(callable $callback, string $expected): void
    {
        $stream   = new CallbackStream($callback);
        $contents = $stream->getContents();
        $this->assertSame($expected, $contents);
    }
}
