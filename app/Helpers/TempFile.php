<?php

namespace App\Helpers;

/**
 * Class TempFile
 * @package App\Helpers
 *
 * The purpose of this helper is to quickly generate temporary files which lasts through the entire lifespan of the
 * application. PHP's tmpfile() auto garbage collection is great for cleaning up files but it only lasts for the
 * duration of the scope. This helper will put the scope in global so only once the entire application is destructed,
 * will the file be deleted
 */
class TempFile
{

    private static $files = [];

    public static function make(): string
    {
        $temp = tmpfile();
        static::$files[] = $temp;

        return stream_get_meta_data($temp)['uri'];
    }

}
