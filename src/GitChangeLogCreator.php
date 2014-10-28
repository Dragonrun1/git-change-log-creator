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
namespace GitChangeLogCreator;

use Exception;

/**
 * Class GitChangeLogCreator
 */
class GitChangeLogCreator
{
    /**
     * @throws Exception
     */
    public function __construct()
    {
        if (!function_exists('shell_exec')) {
            throw new Exception('The shell_exec function has been disabled.');
        }
    }
    /**
     *
     */
    public function __destruct()
    {
        if ($this->fileHandle) {
            @flock($this->fileHandle, LOCK_UN);
            @fclose($this->fileHandle);
        }
    }
    /**
     * @return self
     */
    public function fileContentGenerator()
    {
        $this->logs = array_reverse($this->logs);
        reset($this->logs);
        $this->contents = '';
        foreach ($this->logs as $tag => $commits) {
            $this->contents .= '#### [' . $tag . ']' . PHP_EOL . $commits
                . PHP_EOL;
        }
        return $this;
    }
    /**
     * @return self
     * @throws Exception
     */
    public function fileGenerate()
    {
        $handle = $this->getFileHandle();
        $tries = 0;
        //Give a minute to try writing file.
        $timeout = time() + 60;
        while (strlen($this->contents)) {
            if (++$tries > 10 || time() > $timeout) {
                $this->__destruct();
                $mess = 'Giving up could NOT finish writing  ' . $this->getFileName();
                throw new Exception($mess);
            }
            $written = fwrite($handle, $this->contents);
            // Decrease $tries while making progress but NEVER $tries < 1.
            if ($written > 0 && $tries > 0) {
                --$tries;
            }
            $this->contents = substr($this->contents, $written);
        }
        $this->__destruct();
        return $this;
    }
    /**
     * @return self
     * @throws Exception
     */
    public function getLogs()
    {
        $nextTag = '';
        $count = count($this->tags);
        if ($count === 0) {
            throw new Exception('Does not have any tag.');
        }
        foreach ($this->tags as $v) {
            $gitCommand = $this->gitLog . $this->gitLogOptions . $nextTag . $v;
            $commits = [];
            foreach (explode("\n", shell_exec($gitCommand)) as $commit) {
                if (empty($commit)) {
                    continue;
                }
                if (false !== strpos($commit, 'Merge branch ')) {
                    continue;
                }
                $commits[] = $this->convertLogLine($commit);
            }
            $this->logs[$v] = implode(PHP_EOL, $commits);
            $nextTag = $v . '..';
        }
        return $this;
    }
    /**
     * @return $this
     */
    public function getTags()
    {
        $this->tags = array_unique(explode("\n", shell_exec($this->gitTag)));
        sort($this->tags);
        if ($this->tags[0] == '') {
            $this->tags[0] = 'master';
        }
        sort($this->tags);
        return $this;
    }
    /**
     * @param string $value
     *
     * @return self
     */
    public function setFileName($value = 'CHANGELOG.md')
    {
        $this->fileName = $value;
        return $this;
    }
    /**
     * @param string $log
     *
     * @return string
     */
    protected function convertLogLine($log)
    {
        list($hash, $dateTime, $committer, $message) = explode("\t", $log);
        $hash = substr($hash, 0, $this->hashLength);
        $dateTime = gmdate('c', strtotime($dateTime));
        $message = preg_replace(
            '/#([0-9]+)/m',
            '[#$1](../../issues/$1)',
            $message
        );
        $format = ' * [%1$s](../../commit/%1$s) %2$s (%3$s) - %4$s';
        return sprintf($format, $hash, $dateTime, $committer, $message);
    }
    /**
     * @return resource
     * @throws Exception
     */
    protected function getFileHandle()
    {
        if (empty($this->fileHandle)) {
            $fileName = $this->getFileName();
            $this->fileHandle = fopen($fileName, 'cb');
            if (false === $this->fileHandle) {
                $mess = sprintf('Unable to open %1$s file', $fileName);
                throw new Exception($mess);
            }
            $tries = 0;
            // Give a little time to try getting lock.
            $timeout = time() + 10;
            while (!flock($this->fileHandle, LOCK_EX | LOCK_NB)) {
                if (++$tries > 10 || time() > $timeout) {
                    $this->__destruct();
                    $mess = 'Giving up could NOT get flock on ' . $fileName;
                    throw new Exception($mess);
                }
                // Wait 0.1 to 0.5 seconds before trying again.
                usleep(rand(100000, 500000));
            }
            @ftruncate($this->fileHandle, 0);
        }
        return $this->fileHandle;
    }
    /**
     * @return string
     */
    protected function getFileName()
    {
        if (empty($this->fileName)) {
            $this->fileName = 'CHANGELOG.md';
        }
        return $this->fileName;
    }
    protected $contents;
    protected $fileHandle;
    protected $fileName;
    protected $gitLog = 'git log';
    protected $gitLogOptions = ' --pretty="%H%x09%ai%x09%aN%x09%s" ';
    protected $gitTag = 'git tag';
    protected $hashLength = 10;
    protected $logs;
    protected $tags;
}
