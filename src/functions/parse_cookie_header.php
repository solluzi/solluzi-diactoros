<?php

declare(strict_types=1);

namespace Solluzi\Diactoros;

use function preg_match_all;
use function rawurldecode;

use const PREG_SET_ORDER;

/**
 * Parse a cookie header according to RFC 6265.
 *
 * PHP will replace special characters in cookie names, which results in other cookies not being available due to
 * overwriting. Thus, the server request should take the cookies from the request header instead.
 *
 * @param string $cookieHeader A string cookie header value.
 * @return array<non-empty-string, string> key/value cookie pairs.
 */
function parseCookieHeader($cookieHeader): array
{
    preg_match_all('(
        (?:^\\n?[ \t]*|;[ ])
        (?P<name>[!#$%&\'*+-.0-9A-Z^_`a-z|~]+)
        =
        (?P<DQUOTE>"?)
            (?P<value>[\x21\x23-\x2b\x2d-\x3a\x3c-\x5b\x5d-\x7e]*)
        (?P=DQUOTE)
        (?=\\n?[ \t]*$|;[ ])
    )x', $cookieHeader, $matches, PREG_SET_ORDER);

    $cookies = [];

    foreach ($matches as $match) {
        $cookies[$match['name']] = rawurldecode($match['value']);
    }

    return $cookies;
}
