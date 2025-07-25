<?php

declare(strict_types=1);

namespace Solluzi\Diactoros;

use function get_debug_type;
use function in_array;
use function is_numeric;
use function is_string;
use function ord;
use function preg_match;
use function sprintf;
use function strlen;

/**
 * Provide security tools around HTTP headers to prevent common injection vectors.
 */
final class HeaderSecurity
{
    /**
     * Private constructor; non-instantiable.
     *
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    /**
     * Filter a header value
     *
     * Ensures CRLF header injection vectors are filtered.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * This method filters any values not allowed from the string, and is
     * lossy.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     */
    public static function filter(string $value): string
    {
        $length = strlen($value);
        $string = '';
        for ($i = 0; $i < $length; $i += 1) {
            $ascii = ord($value[$i]);

            // Detect continuation sequences
            if ($ascii === 13) {
                $lf = ord($value[$i + 1]);
                $ws = ord($value[$i + 2]);
                if ($lf === 10 && in_array($ws, [9, 32], true)) {
                    $string .= $value[$i] . $value[$i + 1];
                    $i      += 1;
                }

                continue;
            }

            // Non-visible, non-whitespace characters
            // 9 === horizontal tab
            // 32-126, 128-254 === visible
            // 127 === DEL
            // 255 === null byte
            if (
                ($ascii < 32 && $ascii !== 9)
                || $ascii === 127
                || $ascii > 254
            ) {
                continue;
            }

            $string .= $value[$i];
        }

        return $string;
    }

    /**
     * Validate a header value.
     *
     * Per RFC 7230, only VISIBLE ASCII characters, spaces, and horizontal
     * tabs are allowed in values; header continuations MUST consist of
     * a single CRLF sequence followed by a space or horizontal tab.
     *
     * @see http://en.wikipedia.org/wiki/HTTP_response_splitting
     *
     * @param string|int|float $value
     */
    public static function isValid($value): bool
    {
        $value = (string) $value;

        // Look for:
        // \n not preceded by \r, OR
        // \r not followed by \n, OR
        // \r\n not followed by space or horizontal tab; these are all CRLF attacks
        if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $value)) {
            return false;
        }

        // Non-visible, non-whitespace characters
        // 9 === horizontal tab
        // 10 === line feed
        // 13 === carriage return
        // 32-126, 128-254 === visible
        // 127 === DEL (disallowed)
        // 255 === null byte (disallowed)
        if (preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $value)) {
            return false;
        }

        return true;
    }

    /**
     * Assert a header value is valid.
     *
     * @param mixed $value Value to be tested. This method asserts it is a string or number.
     * @throws Exception\InvalidArgumentException For invalid values.
     */
    public static function assertValid(mixed $value): void
    {
        if (! is_string($value) && ! is_numeric($value)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid header value type; must be a string or numeric; received %s',
                get_debug_type($value)
            ));
        }
        if (! self::isValid($value)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" is not valid header value',
                $value
            ));
        }
    }

    /**
     * Assert whether or not a header name is valid.
     *
     * @see http://tools.ietf.org/html/rfc7230#section-3.2
     *
     * @throws Exception\InvalidArgumentException
     */
    public static function assertValidName(mixed $name): void
    {
        if (! is_string($name)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid header name type; expected string; received %s',
                get_debug_type($name)
            ));
        }
        if (! preg_match('/^[a-zA-Z0-9\'`#$%&*+.^_|~!-]+$/D', $name)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '"%s" is not valid header name',
                $name
            ));
        }
    }
}
