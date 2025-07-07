# Migration to Version 2

If you are only using the PSR-7 implementations (e.g., `Request`, `Response`,
`ServerRequest`, etc.), migrating to v2 can be done by updating your
Solluzi/Solluzi-diactoros constraint in your `composer.json`. You have two
options for doing so:

- Adopt the v2 release specifically:

  ```bash
  $ composer require "Solluzi/Solluzi-diactoros:^2.0"
  ```

- Update your constraint to allow either version:
  
    - Edit the constraint in your `composer.json` to read:

      ```json
      "Solluzi/Solluzi-diactoros": "^1.8.6 || ^2.0"
      ```

    - Update your dependencies:

      ```bash
      $ composer update
      ```

The first approach may fail if libraries you depend on specifically require a
version 1 release. The second approach may leave you on a version 1 release in
situations where other libraries you depend on require version 1.

## Changed

- `Solluzi\Diactoros\RequestTrait` now raises an `InvalidArgumentException` in
  `withMethod()` for invalid HTTP method values.

- `Solluzi\Diactoros\Serializer\Request::toString()` no longer raises an
  `UnexpectedValueException` due to an unexpected HTTP method; this is due to the
  fact that the HTTP method value can no longer be set to an invalid value.

- `Solluzi\Diactoros\marshalHeadersFromSapi()` is parsing headers differently compared to the legacy implementation.
   As a consequence Headers with `'0'` as values will be part of the parsed Headers.
   In former versions those headers were ignored.
   Usages of `\Solluzi\Diactoros\MessageTrait::hasHeader()` and `\Solluzi\Diactoros\MessageTrait::getHeader()`
   might be affected if you are using `ServerRequestFactory::fromGlobals()` functionality.

## Removed

Several features were removed for version 2. These include removal of the
`Emitter` functionality, the `Server` implementation, and a number of methods on
the `ServerRequestFactory`.

### Emitters

`Solluzi\Diactoros\Response\EmitterInterface` and all emitter implementations were
removed from Solluzi-diactoros. They are now available in the
[Solluzi/Solluzi-httphandlerrunner package](https://docs.Solluzi.dev/Solluzi-httphandlerrunner).
In most cases, these can be replaced by changing the namespace of imported
classes from `Solluzi\Diactoros\Response` to `Solluzi\HttpHandlerRunner\Emitter`.

### Server

The `Solluzi\Diactoros\Server` class has been removed. We recommend using the
`RequestHandlerRunner` class from [Solluzi/Solluzi-httphandlerrunner](https://docs.Solluzi.dev/Solluzi-httphandlerrunner)
to provide these capabilities instead. Usage is similar, but the
`RequestHandlerRunner` provides better error handling, and integration with
emitters.

### ServerRequestFactory Methods

A number of public static methods have been removed from
`ServerRequestFactory`. The following table details the methods removed, and
replacements you may use if you still require the functionality.

Method Removed                    | Replacement functionality
--------------------------------- | -------------------------
`normalizeServer()`               | `Solluzi\Diactoros\normalizeServer()`
`marshalHeaders()`                | `Solluzi\Diactoros\marshalHeadersFromSapi()`
`marshalUriFromServer()`          | `Solluzi\Diactoros\marshalUriFromSapi()`
`marshalRequestUri()`             | `Uri::getPath()` from the `Uri` instance returned by `marshalUriFromSapi()`
`marshalHostAndPortFromHeaders()` | `Uri::getHost()` and `Uri::getPort()` from the `Uri` instances returned by `marshalUriFromSapi()`
`stripQueryString()`              | `explode("?", $path, 2)[0]`
`normalizeFiles()`                | `Solluzi\Diactoros\normalizeUploadedFiles()`
