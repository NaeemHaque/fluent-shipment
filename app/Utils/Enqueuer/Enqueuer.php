<?php

namespace FluentShipment\App\Utils\Enqueuer;

use FluentShipment\App\App;

abstract class Enqueuer
{
    protected static $resourceDirectory = 'resources/';

    public static function getResourceDirectory()
    {
        return static::$resourceDirectory;
    }

    public static function getAssetPath()
    {
        return App::getInstance()['url.assets'];
    }

    public static function getProductionFilePath($file)
    {
        return static::getAssetPath() . $file['file'];
    }

    public static function getEnqueuePath($path = '')
    {
        return static::getAssetPath() . $path;
    }
}
