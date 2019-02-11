<?php
declare(strict_types=1);

/**
 * Contains bootstrap.
 *
 * PHP version 7.1
 *
 * LICENSE:
 * This file is part of git-change-log-creator which is used to create an
 * updated change log file from Git log.
 * Copyright (C) 2014-2019 Michael Cummings
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see
 * <https://opensource.org/licenses/GPL-2.0>.
 *
 * You should be able to find a copy of this license in the LICENSE file.
 *
 * @copyright 2014-2019 Michael Cummings
 * @license   GPL-2.0
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */

use Composer\Autoload\ClassLoader;

/*
 * Nothing to do if Composer auto loader already exists.
 */
if (class_exists(ClassLoader::class, false)) {
    return 0;
}
/*
 * Find Composer auto loader after striping away any vendor path.
 */
$path = str_replace('\\', '/', dirname(__DIR__));
$vendorPos = strpos($path, 'vendor/');
if (false !== $vendorPos) {
    $path = substr($path, 0, $vendorPos);
}
$path .= '/vendor/autoload.php';
/*
 * Turn off warning messages for the following include.
 */
$errorReporting = error_reporting(E_ALL & ~E_WARNING);
/** @noinspection PhpIncludeInspection */
include_once $path;
error_reporting($errorReporting);
unset($errorReporting, $path, $vendorPos);
if (!class_exists(ClassLoader::class, false)) {
    $mess = 'Could NOT find required Composer class auto loader. Aborting ...';
    if ('cli' === PHP_SAPI) {
        fwrite(STDERR, $mess);
    } else {
        fwrite(STDOUT, $mess);
    }
    unset($mess);
    exit(1);
}
