<?php 
declare(strict_types=1);

namespace sigawa\mvccore\support;
use DateTimeImmutable;
use Exception;

class Str
{
    /**
     * Generate a ULID (Universally Unique Lexicographically Sortable Identifier).
     * 26-char Crockford Base32 encoded.
     */
    public static function ulid(): string
    {
        $time = (int) (microtime(true) * 1000);
        $timeChars = self::encodeTime($time, 10);

        $randomChars = '';
        for ($i = 0; $i < 16; $i++) {
            $randomChars .= self::ENCODING[random_int(0, 31)];
        }

        return $timeChars . $randomChars;
    }

    /** Quick random string generator */
    public static function random(int $length = 16): string
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $poolLen = strlen($pool);

        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $pool[random_int(0, $poolLen - 1)];
        }
        return $result;
    }

    /** Check if string starts with given needle */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }

    /** Check if string ends with given needle */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, -strlen($needle)) === $needle;
    }

    /** Convert string to snake_case */
    public static function snake(string $value): string
    {
        $value = preg_replace('/\s+/u', '', ucwords($value));
        $value = preg_replace('/(.)(?=[A-Z])/u', '$1_', $value);
        return strtolower($value ?? '');
    }

    /** Convert string to camelCase */
    public static function camel(string $value): string
    {
        $value = str_replace('_', ' ', strtolower($value));
        $value = str_replace(' ', '', ucwords($value));
        return lcfirst($value);
    }

    /** Convert string to StudlyCase */
    public static function studly(string $value): string
    {
        $value = str_replace('_', ' ', strtolower($value));
        $value = str_replace(' ', '', ucwords($value));
        return $value;
    }

    /** Internal Crockford Base32 alphabet */
    private const ENCODING = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private static function encodeTime(int $time, int $length): string
    {
        $encoded = '';
        for ($i = $length - 1; $i >= 0; $i--) {
            $mod = $time & 31;
            $encoded = self::ENCODING[$mod] . $encoded;
            $time >>= 5;
        }
        return $encoded;
    }
}