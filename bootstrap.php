<?php
/**
 * Contains Yapeal Bootstrap.
 *
 * PHP version 5.4
 *
 * LICENSE:
 * This file is part of Yet Another Php Eve Api Library also know as Yapeal
 * which can be used to access the Eve Online API data and place it into a
 * database. Copyright (C) 2014 Michael Cummings
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE.md file. A
 * copy of the GNU GPL should also be available in the GNU-GPL.md file.
 *
 * @copyright 2014 Michael Cummings
 * @license   http://www.gnu.org/copyleft/lesser.html GNU LGPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
namespace GitChangeLogCreator;

/*
* Find auto loader from one of
* vendor/bin/
* OR ./
* OR bin/
* OR lib/PhpEOL/
* OR vendor/PhpEOL/PhpEOL/bin/
*/
(@include_once dirname(__DIR__) . '/autoload.php')
|| (@include_once __DIR__ . '/vendor/autoload.php')
|| (@include_once dirname(__DIR__) . '/vendor/autoload.php')
|| (@include_once dirname(dirname(__DIR__)) . '/vendor/autoload.php')
|| (@include_once dirname(dirname(dirname(__DIR__))) . '/autoload.php')
|| die('Could not find required auto class loader. Aborting ...');
