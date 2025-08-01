<?php

declare(strict_types=1);

namespace Solluzi\Diactoros;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

use function array_key_exists;
use function gettype;
use function is_array;
use function is_object;
use function sprintf;

/**
 * Server-side HTTP request
 *
 * Extends the Request definition to add methods for accessing incoming data,
 * specifically server parameters, cookies, matched path parameters, query
 * string arguments, body parameters, and upload file information.
 *
 * "Attributes" are discovered via decomposing the request (and usually
 * specifically the URI path), and typically will be injected by the application.
 *
 * Requests are considered immutable; all methods that might change state are
 * implemented such that they retain the internal state of the current
 * message and return a new instance that contains the changed state.
 */
class ServerRequest implements ServerRequestInterface
{
    use RequestTrait;

    private array $attributes = [];

    private array $uploadedFiles;

    /**
     * @param array $serverParams Server parameters, typically from $_SERVER
     * @param array $uploadedFiles Upload file information, a tree of UploadedFiles
     * @param null|string|UriInterface $uri URI for the request, if any.
     * @param null|string $method HTTP method for the request, if any.
     * @param string|resource|StreamInterface $body Message body, if any.
     * @param array $headers Headers for the message, if any.
     * @param array $cookieParams Cookies for the message, if any.
     * @param array $queryParams Query params for the message, if any.
     * @param null|array|object $parsedBody The deserialized body parameters, if any.
     * @param string $protocol HTTP protocol version.
     * @throws Exception\InvalidArgumentException For any invalid value.
     */
    public function __construct(
        private array $serverParams = [],
        array $uploadedFiles = [],
        null|string|UriInterface $uri = null,
        ?string $method = null,
        $body = 'php://input',
        array $headers = [],
        private array $cookieParams = [],
        private array $queryParams = [],
        private $parsedBody = null,
        string $protocol = '1.1'
    ) {
        $this->validateUploadedFiles($uploadedFiles);

        if ($body === 'php://input') {
            $body = new Stream($body, 'r');
        }

        $this->initialize($uri, $method, $body, $headers);
        $this->uploadedFiles = $uploadedFiles;
        $this->protocol      = $protocol;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerParams(): array
    {
        return $this->serverParams;
    }

    /**
     * {@inheritdoc}
     */
    public function getUploadedFiles(): array
    {
        return $this->uploadedFiles;
    }

    /**
     * {@inheritdoc}
     */
    public function withUploadedFiles(array $uploadedFiles): ServerRequest
    {
        $this->validateUploadedFiles($uploadedFiles);
        $new                = clone $this;
        $new->uploadedFiles = $uploadedFiles;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getCookieParams(): array
    {
        return $this->cookieParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withCookieParams(array $cookies): ServerRequest
    {
        $new               = clone $this;
        $new->cookieParams = $cookies;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    /**
     * {@inheritdoc}
     */
    public function withQueryParams(array $query): ServerRequest
    {
        $new              = clone $this;
        $new->queryParams = $query;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getParsedBody()
    {
        return $this->parsedBody;
    }

    /**
     * {@inheritdoc}
     */
    public function withParsedBody($data): ServerRequest
    {
        if (! is_array($data) && ! is_object($data) && null !== $data) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects a null, array, or object argument; received %s',
                __METHOD__,
                gettype($data)
            ));
        }

        $new             = clone $this;
        $new->parsedBody = $data;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name, $default = null)
    {
        if (! array_key_exists($name, $this->attributes)) {
            return $default;
        }

        return $this->attributes[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function withAttribute(string $name, $value): ServerRequest
    {
        $new                    = clone $this;
        $new->attributes[$name] = $value;
        return $new;
    }

    /**
     * {@inheritdoc}
     */
    public function withoutAttribute(string $name): ServerRequest
    {
        $new = clone $this;
        unset($new->attributes[$name]);
        return $new;
    }

    /**
     * Recursively validate the structure in an uploaded files array.
     *
     * @throws Exception\InvalidArgumentException If any leaf is not an UploadedFileInterface instance.
     */
    private function validateUploadedFiles(array $uploadedFiles): void
    {
        foreach ($uploadedFiles as $file) {
            if (is_array($file)) {
                $this->validateUploadedFiles($file);
                continue;
            }

            if (! $file instanceof UploadedFileInterface) {
                throw new Exception\InvalidArgumentException('Invalid leaf in uploaded files structure');
            }
        }
    }
}
