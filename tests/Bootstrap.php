<?php

/*
 * This file is part of the Stomp package.
 *
 * (c) Kirill Skatov <kirill@noadmin.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
