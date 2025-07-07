# API

## Request Message

`Solluzi\Diactoros\Request` implements [`Psr\Http\Message\RequestInterface`](https://github.com/php-fig/http-message/blob/master/src/RequestInterface.php),
and is intended for client-side requests. It includes the following methods:

```php
class Request
{
    public function __construct(
        $uri = null,
        $method = null,
        $body = 'php://memory',
        array $headers = []
    );

    // See psr/http-message's RequestInterface for other methods
}
```

Requests are immutable. Any methods that would change state &mdash; those prefixed with `with` and
`without` &mdash; all return a new instance with the changes requested.

## ServerRequest Message

For server-side applications, `Solluzi\Diactoros\ServerRequest` implements
[`Psr\Http\Message\ServerRequestInterface`](https://github.com/php-fig/http-message/blob/master/src/ServerRequestInterface.php),
which provides access to the elements of an HTTP request, as well as uniform access to the various
elements of incoming data. The methods included are:

```php
class ServerRequest
{
    public function __construct(
        array $serverParams = [],
        array $fileParams = [],
        $uri = null,
        $method = null,
        $body = 'php://input',
        array $headers = []
    );

    // See psr/http-message's ServerRequestInterface for other methods.
}
```

The `ServerRequest` is immutable. Any methods that would change state &mdash; those prefixed with `with`
and `without` &mdash; all return a new instance with the changes requested. Server parameters are
considered completely immutable, however, as they cannot be recalculated, and, rather, is a source
for other values.

## Response Message

`Solluzi\Diactoros\Response` provides an implementation of
[`Psr\Http\Message\ResponseInterface`](https://github.com/php-fig/http-message/blob/master/src/ResponseInterface.php),
an object to be used to aggregate response information for both HTTP clients and server-side
applications, including headers and message body content. It includes the following:

```php
class Response
{
    public function __construct(
        $body = 'php://memory',
        $statusCode = 200,
        array $headers = []
    );

    // See psr/http-message's ResponseInterface for other methods
}
```

Like the `Request` and `ServerRequest`, responses are immutable. Any methods that would change state
&mdash; those prefixed with `with` and `without` &mdash; all return a new instance with the changes requested.

### HtmlResponse and JsonResponse

The most common use case in server-side applications for generating responses is to provide a string
to use for the response, typically HTML or data to serialize as JSON.  `Solluzi\Diactoros\Response\HtmlResponse` and `Solluzi\Diactoros\Response\JsonResponse` exist to facilitate these use cases:

```php
$htmlResponse = new HtmlResponse($html);

$jsonResponse = new JsonResponse($data);
```

In the first example, you will receive a response with a stream containing the HTML; additionally,
the `Content-Type` header will be set to `text/html`. In the second case, the stream will contain a
stream containing the JSON-serialized `$data`, and have a `Content-Type` header set to
`application/json`.

Both objects allow passing the HTTP status, as well as any headers you want to specify,
including the `Content-Type` header:

```php
$htmlResponse = new HtmlResponse($html, 404, [
    'Content-Type' => [ 'application/xhtml+xml' ],
]);

$jsonResponse = new JsonResponse($data, 422, [
    'Content-Type' => [ 'application/problem+json' ],
]);
```

## ServerRequestFactory

This static class can be used to marshal a `ServerRequest` instance from the PHP environment.
The primary entry point is `Solluzi\Diactoros\ServerRequestFactory::fromGlobals(array $server, array $query, array $body, array $cookies, array $files, ?Solluzi\Diactoros\ServerRequestFilter\FilterServerRequestInterface $requestFilter)`.
This method will create a new `ServerRequest` instance with the data provided.
Examples of usage are:

```php
// Returns new ServerRequest instance, using values from superglobals:
$request = ServerRequestFactory::fromGlobals();

// or

// Returns new ServerRequest instance, using values provided (in this
// case, equivalent to the previous!)
$request = ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);
```

### Request Filters

Since version 2.11.1, this method takes the additional optional argument `$requestFilter`.
This should be a `null` value, or an instance of [`Solluzi\Diactoros\ServerRequestFilter\FilterServerRequestInterface`](server-request-filters.md).
For version 2 releases, if a `null` is provided, internally the method will assign a [`Solluzi\Diactoros\ServerRequestFilter\FilterUsingXForwardedHeaders`](server-request-filters.md#filterusingxforwardedheaders) instance configured as follows:

```php
$requestFilter = $requestFilter ?: FilterUsingXForwardedHeaders::trustReservedSubnets();
```

The request filter is called on the generated server request instance, and its result is returned from `fromGlobals()`.

**For version 3 releases, this method will switch to using a `Solluzi\Diactoros\ServerRequestFilter\DoNotFilter` by default.**
If you are using this factory method directly, please be aware and update your code accordingly.

### ServerRequestFactory Helper Functions

In order to create the various artifacts required by a `ServerRequest` instance,
Diactoros also provides a number of functions under the `Solluzi\Diactoros`
namespace for introspecting the SAPI `$_SERVER` parameters, headers, `$_FILES`,
and even the `Cookie` header. These include:

- `Solluzi\Diactoros\normalizeServer(array $server, callable $apacheRequestHeaderCallback = null) : array`
  (its main purpose is to aggregate the `Authorization` header in the SAPI params
  when under Apache)
- `Solluzi\Diactoros\marshalProtocolVersionFromSapi(array $server) : string`
- `Solluzi\Diactoros\marshalMethodFromSapi(array $server) : string`.
- `Solluzi\Diactoros\marshalUriFromSapi(array $server, array $headers) : Uri`.
  Please note: **this function is deprecated as of version 2.11.1**, and no longer used in `ServerRequestFactory::fromGlobals()`.
  Use `ServerRequestFactory::fromGlobals()` instead.
- `Solluzi\Diactoros\marshalHeadersFromSapi(array $server) : array`
- `Solluzi\Diactoros\parseCookieHeader(string $header) : array`
- `Solluzi\Diactoros\createUploadedFile(array $spec) : UploadedFile` (creates the
  instance from a normal `$_FILES` entry)
- `Solluzi\Diactoros\normalizeUploadedFiles(array $files) : UploadedFileInterface[]`
  (traverses a potentially nested array of uploaded file instances and/or
  `$_FILES` entries, including those aggregated under mod_php, php-fpm, and
  php-cgi in order to create a flat array of `UploadedFileInterface` instances
  to use in a request)

## URI

`Solluzi\Diactoros\Uri` is an implementation of
[`Psr\Http\Message\UriInterface`](https://github.com/php-fig/http-message/blob/master/src/UriInterface.php),
and models and validates URIs. It implements `__toString()`, allowing it to be represented as a
string and `echo()`'d directly. The following methods are pertinent:

```php
class Uri
{
    public function __construct($uri = '');

    // See psr/http-message's UriInterface for other methods.
}
```

Like the various message objects, URIs are immutable. Any methods that would
change state &mdash; those
prefixed with `with` and `without` &mdash; all return a new instance with the changes requested.

## Stream

`Solluzi\Diactoros\Stream` is an implementation of
[`Psr\Http\Message\StreamInterface`](https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php),
and provides a number of facilities around manipulating the composed PHP stream resource. The
constructor accepts a stream, which may be one of:

- A stream identifier; e.g., `php://input`, a filename, etc.
- A PHP stream resource

If a stream identifier is provided, an optional second parameter may be provided, the file mode by
which to `fopen` the stream.

`ServerRequest` objects by default use a `php://input` stream set to read-only; `Response` objects
by default use a `php://memory` with a mode of `wb+`, allowing binary read/write access.

In most cases, you will not interact with the Stream object directly.

## UploadedFile

`Solluzi\Diactoros\UploadedFile` is an implementation of
[`Psr\Http\Message\UploadedFileInterface`](https://github.com/php-fig/http-message/blob/master/src/UploadedFileInterface.php),
and provides abstraction around a single uploaded file, including behavior for interacting with it
as a stream or moving it to a filesystem location.

In most cases, you will only use the methods defined in the `UploadedFileInterface`.
