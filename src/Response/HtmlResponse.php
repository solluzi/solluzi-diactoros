<?php

declare(strict_types=1);

namespace Solluzi\Diactoros\Response;

use Solluzi\Diactoros\Exception;
use Solluzi\Diactoros\Response;
use Solluzi\Diactoros\Stream;
use Psr\Http\Message\StreamInterface;

use function get_debug_type;
use function is_string;
use function sprintf;

/**
 * HTML response.
 *
 * Allows creating a response by passing an HTML string to the constructor;
 * by default, sets a status code of 200 and sets the Content-Type header to
 * text/html.
 */
class HtmlResponse extends Response
{
    use InjectContentTypeTrait;

    /**
     * Create an HTML response.
     *
     * Produces an HTML response with a Content-Type of text/html and a default
     * status of 200.
     *
     * @param string|StreamInterface $html HTML or stream for the message body.
     * @param int $status Integer status code for the response; 200 by default.
     * @param array $headers Array of headers to use at initialization.
     * @throws Exception\InvalidArgumentException If $html is neither a string or stream.
     */
    public function __construct($html, int $status = 200, array $headers = [])
    {
        parent::__construct(
            $this->createBody($html),
            $status,
            $this->injectContentType('text/html; charset=utf-8', $headers)
        );
    }

    /**
     * Create the message body.
     *
     * @param string|StreamInterface $html
     * @throws Exception\InvalidArgumentException If $html is neither a string or stream.
     */
    private function createBody($html): StreamInterface
    {
        if ($html instanceof StreamInterface) {
            return $html;
        }

        if (! is_string($html)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid content (%s) provided to %s',
                get_debug_type($html),
                self::class
            ));
        }

        $body = new Stream('php://temp', 'wb+');
        $body->write($html);
        $body->rewind();
        return $body;
    }
}
