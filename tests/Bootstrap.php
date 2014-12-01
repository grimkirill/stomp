<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kirill
 * Date: 10.01.13
 * Time: 16:44
 * To change this template use File | Settings | File Templates.
 */

spl_autoload_register(function ($class) {
    if ('\\' == $class[0]) {
        $class = substr($class, 1);
    }
    $logicalPath =  dirname(__DIR__) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR .  strtr($class, '\\', DIRECTORY_SEPARATOR) . '.php';

    if (file_exists($logicalPath)) {
        include $logicalPath;
        return true;
    }
    return false;
}, true);
