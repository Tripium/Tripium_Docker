<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit617f5dff30e45fdf01f9f6bc1708d984
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WilcityWPMLAPP\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WilcityWPMLAPP\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit617f5dff30e45fdf01f9f6bc1708d984::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit617f5dff30e45fdf01f9f6bc1708d984::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit617f5dff30e45fdf01f9f6bc1708d984::$classMap;

        }, null, ClassLoader::class);
    }
}