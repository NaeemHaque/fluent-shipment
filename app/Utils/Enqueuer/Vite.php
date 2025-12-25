<?php

namespace FluentShipment\App\Utils\Enqueuer;

use Exception;
use FluentShipment\App\App;
use FluentShipment\Framework\Support\Arr;

class Vite extends Enqueuer
{
    /**
     * @method static enqueueScript($handle, $src, $dependency = [], $version = null, $inFooter = false)
     * 
     * @method static enqueueStyle($handle, $src, $dependency = [], $version = null)
     */

    private $moduleScripts = [];

    private $isScriptFilterAdded = false;

    private static $viteHostProtocol = 'http://';

    private static $viteHost = 'localhost';

    private static $vitePort = '8880';

    protected static $instance = null;

    protected static $lastJsHandle = null;

    private $manifestData = null;

    private function loadViteManifest()
    {
        if (!empty($this->manifestData)) {
            return;
        }

        $manifestPath = App::make('path.assets') . 'manifest.json';

        if (!file_exists($manifestPath)) {
            throw new Exception(
                'Vite Manifest Not Found. Run : npm run dev or npm run prod'
            );
        }

        $manifestFile = fopen($manifestPath, "r");

        $manifestData = fread($manifestFile, filesize($manifestPath));

        $this->manifestData = json_decode($manifestData, true);
    }

    private function enqueueScript(
        $handle,
        $src,
        $dependency = [],
        $version = null,
        $inFooter = false
    )
    {
        if (in_array($handle, $this->moduleScripts) && static::isOnDevMode()) {
            $callerReference = debug_backtrace()[2];
            $fileName = explode('plugins', $callerReference['file'])[1];
            $line = $callerReference['line'];
        }

        $this->moduleScripts[] = $handle;
        static::$lastJsHandle = $handle;

        if (!$this->isScriptFilterAdded) {
            add_filter('script_loader_tag', function ($tag, $handle, $src) {
                return $this->addModuleToScript($tag, $handle, $src);
            }, 10, 3);

            $this->isScriptFilterAdded = true;
        }

        $srcPath = static::isOnDevMode()
            ? static::getVitePath() . $src
            : static::getProductionFilePath(
                $this->getFileFromManifest($src)
            );

        if (!static::isOnDevMode()) {
            static::enqueueDependentRecursiveStyle(
                $this->getFileFromManifest($src)
            );
        }

        wp_enqueue_script(
            $handle, $srcPath, $dependency, $version, $inFooter
        );

        return $this;
    }

    private function enqueueStyle(
        $handle,
        $src,
        $dependency = [],
        $version = null
    )
    {
        $srcPath = static::isOnDevMode()
            ? static::getVitePath() . $src
            : static::getProductionFilePath($this->getFileFromManifest($src));

        if (!static::isOnDevMode()) {
            static::enqueueDependentRecursiveStyle(
                $this->getFileFromManifest($src)
            );
        }

        wp_enqueue_style($handle, $srcPath, $dependency, $version);
    }

    private function getFileFromManifest($src)
    {
        $fullKey = static::getResourceDirectory() . $src;

        if (isset($this->manifestData[$fullKey])) {
            return $this->manifestData[$fullKey];
        }

        if (static::isOnDevMode()) {
            throw new Exception("$src file not found in Vite manifest");
        }

        return [];
    }

    public static function enqueueDependentRecursiveStyle($file)
    {
        $assetPath = static::getAssetPath();

        if (isset($file['css']) && is_array($file['css'])) {

            foreach ($file['css'] as $key => $path) {
                wp_enqueue_style(
                    $file['file'] . '_' . $key . '_css', $assetPath . $path
                );
            }
        }
    }

    public static function with($params)
    {
        if (!Arr::isAssoc($params) || empty(static::$lastJsHandle)) {
            static::$lastJsHandle = null;
            return;
        }

        foreach ($params as $key => $val) {
            wp_localize_script(static::$lastJsHandle, $key, $val);
        }

        static::$lastJsHandle = null;
    }

    private function enqueueStaticScript(
        $handle,
        $src,
        $dependency = [],
        $version = null,
        $inFooter = false
    )
    {
        wp_enqueue_script(
            $handle,
            static::getEnqueuePath($src),
            $dependency,
            $version,
            $inFooter
        );
    }

    private function enqueueStaticStyle(
        $handle,
        $src,
        $dependency = [],
        $version = null
    )
    {
        wp_enqueue_style(
            $handle, static::getEnqueuePath($src), $dependency, $version
        );
    }

    public static function isOnDevMode()
    {
        return App::getInstance()->config->get('app.env') === 'dev';
    }

    public static function getVitePath()
    {
        return static::$viteHostProtocol
            . static::$viteHost . ':'
            . static::$vitePort . '/'
            . static::getResourceDirectory();
    }

    public static function getEnqueuePath($path = '')
    {
        return (
            static::isOnDevMode()
                ? static::getVitePath()
                : static::getAssetPath()
            ) . $path;
    }

    public static function getStaticFilePath($path = '')
    {
        return static::getEnqueuePath($path);
    }

    private function addModuleToScript($tag, $handle, $src)
    {
        if (in_array($handle, $this->moduleScripts)) {
            return wp_get_script_tag([
                'src' => esc_url($src),
                'type' => 'module'
            ]);
        }

        return $tag;
    }

    public static function __callStatic($method, $params)
    {
        if (static::$instance === null) {
            
            static::$instance = new static();

            if (!static::isOnDevMode()) {
                static::$instance->loadViteManifest();
            }
        }

        return call_user_func_array([static::$instance, $method], $params);
    }
}
