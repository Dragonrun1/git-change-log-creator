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

use DomainException;
use Exception;
use RuntimeException;

/**
 * Class GitChangeLogCreator
 */
class GitChangeLogCreator
{
    /**
     * @throws RunTimeException
     */
    public function __construct()
    {
        if (!function_exists('shell_exec')) {
            $mess = 'The shell_exec function has been disabled.';
            throw new RunTimeException($mess);
        }
        $this->setHashLength();
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
    public function contentGenerator()
    {
        $this->logs = array_reverse($this->logs);
        reset($this->logs);
        $this->contents = $this->getFileHeader(array_keys($this->logs));
        foreach ($this->logs as $tag => $commits) {
            $tag = htmlentities($tag, ENT_QUOTES | ENT_DISALLOWED | ENT_HTML5, 'UTF-8');
            $this->contents .= '## [' . $tag . '](../../tree/' . $tag . ')'
                . PHP_EOL . $commits . PHP_EOL;
        }
        $this->contents .= $this->getFileFooter();
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
     * @return string
     */
    public function getContents()
    {
        return $this->contents;
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
    public function setFileFooter($value)
    {
        $this->fileFooter = (string)$value;
        return $this;
    }
    /**
     * @param string $value
     *
     * @return self
     */
    public function setFileHeader($value)
    {
        $this->fileHeader = (string)$value;
        return $this;
    }
    /**
     * @param string $value
     *
     * @return self
     */
    public function setFileName($value = 'CHANGELOG.md')
    {
        $this->fileName = (string)$value;
        return $this;
    }
    /**
     * @param int $value
     *
     * @throws DomainException
     * @return self
     */
    public function setHashLength($value = 10)
    {
        $value = (int)$value;
        if ($value < 1) {
            throw new DomainException('Hash length must be > 0 was given ' .
                $value);
        }
        $this->hashLength = $value;
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
        $hashName = substr($hash, 0, $this->getHashLength());
        $dateTime = gmdate('c', strtotime($dateTime));
        $message = preg_replace(
            '/#([0-9]+)/m',
            '[#$1](../../issues/$1)',
            $message
        );
        $message = htmlentities($message,
            ENT_QUOTES | ENT_DISALLOWED | ENT_HTML5, 'UTF-8');
        $format = ' * [%1$s](../../commit/%2$s) %3$s (%4$s) - %5$s';
        return sprintf($format, $hashName, $hash, $dateTime, $committer, $message);
    }
    /**
     * @return string
     */
    protected function getFileFooter()
    {
        return $this->fileFooter;
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
     * @param string[] $tags
     *
     * @return string
     */
    protected function getFileHeader(array $tags)
    {
        $fileName = $this->getFileName() . PHP_EOL
            . str_repeat('=', strlen($this->getFileName()));
        $toc = $this->getTableOfContents($tags);
        $replace = [$fileName, $toc];
        return str_replace(
            ['{fileName}', '{toc}'],
            $replace,
            $this->fileHeader);
    }
    /**
     * @return string
     */
    protected function getFileName()
    {
        if (empty($this->fileName)) {
            $this->setFileName();
        }
        return $this->fileName;
    }
    /**
     * @return int
     */
    protected function getHashLength()
    {
        if (empty($this->hashLength)) {
            $this->setHashLength();
        }
        return $this->hashLength;
    }
    /**
     * @param array $tags
     *
     * @return string
     */
    protected function getTableOfContents(array $tags)
    {
        $toc = '';
        foreach ($tags as $tag) {
            $$tag = htmlentities($tag, ENT_QUOTES | ENT_DISALLOWED | ENT_HTML5,
                'UTF-8');
            $toc .= ' * [' . $tag . '](#' . $tag . ')' . PHP_EOL;
        }
        return $toc;
    }
    /**
     * @type string $contents
     */
    protected $contents;
    /**
     * @type string $fileFooter
     */
    protected $fileFooter = <<<'FOOT'

Create with [Git Change Log Creator](https://github.com/Dragonrun1/git-change-log-creator)
FOOT;
    /**
     * @type resource $fileHandle
     */
    protected $fileHandle;
    /**
     * @type string $fileHeader
     */
    protected $fileHeader = <<<'HEAD'
{fileName}

Auto-generated from Git log.

## Table of Contents

{toc}

HEAD;
    /**
     * @type string $fileName
     */
    protected $fileName;
    /**
     * @type string $gitLog
     */
    protected $gitLog = 'git log';
    /**
     * @type string $gitLogOptions
     */
    protected $gitLogOptions = ' --pretty="%H%x09%ai%x09%aN%x09%s" ';
    /**
     * @type string $gitTag
     */
    protected $gitTag = 'git tag';
    /**
     * @type int $hashLength Sets number of characters to use from Git hash
     *       link text.
     */
    protected $hashLength;
    /**
     * @type string[] $logs
     */
    protected $logs;
    /**
     * @type string[] $tags
     */
    protected $tags;
}
