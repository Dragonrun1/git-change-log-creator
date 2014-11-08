<?php
/**
 * Contains GitChangeLogCreator class.
 *
 * PHP version 5.4
 *
 * LICENSE:
 * This file is part of git-change-log-creator which is used to create an
 * updated change log file from Git log.
 * Copyright (C) 2014 Michael Cummings
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
 * <http://www.gnu.org/licenses/>.
 *
 * You should be able to find a copy of this license in the LICENSE file.
 *
 * @copyright 2014 Michael Cummings
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU GPL
 * @author    Michael Cummings <mgcummings@yahoo.com>
 */
use GitChangeLogCreator\GitChangeLogCreator;

include_once __DIR__ . '/bootstrap.php';
try {
    $git = new GitChangeLogCreator();
    $git->getTags()
        ->getLogs()
        ->contentGenerator()
        ->fileGenerate();
} catch (Exception $e) {
    echo $e->getMessage();
}
