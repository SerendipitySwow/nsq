<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SerendipitySwow\Nsq;

class Packer
{
    public static function packUInt32($int): string
    {
        return pack('N', $int);
    }

    public static function unpackUInt32($int): bool | array
    {
        return unpack('N', $int);
    }

    public static function unpackInt64($int)
    {
        return unpack('q', $int)[1];
    }

    public static function unpackUInt16($int)
    {
        return unpack('n', $int)[1];
    }

    public static function unpackString(string $content): string
    {
        $size = strlen($content);
        $bytes = unpack("c{$size}chars", $content);
        $string = '';
        foreach ($bytes as $byte) {
            $string .= chr($byte);
        }

        return $string;
    }
}
