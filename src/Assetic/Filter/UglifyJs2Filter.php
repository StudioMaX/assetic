<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Filter;

use Assetic\Asset\AssetInterface;
use Assetic\Exception\FilterException;
use Assetic\Util\FilesystemUtils;
use Symfony\Component\Process\Process;

/**
 * UglifyJs2 filter.
 *
 * @link   http://lisperator.net/uglifyjs
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class UglifyJs2Filter extends BaseNodeFilter
{
    private $uglifyjsBin;
    private $nodeBin;
    private $compress;
    private $beautify;
    private $mangle;
    private $screwIe8;
    private $comments;
    private $wrap;
    private $defines;

    public function __construct($uglifyjsBin = '/usr/bin/uglifyjs', $nodeBin = null)
    {
        $this->uglifyjsBin = $uglifyjsBin;
        $this->nodeBin = $nodeBin;
    }

    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    public function setBeautify($beautify)
    {
        $this->beautify = $beautify;
    }

    public function setMangle($mangle)
    {
        $this->mangle = $mangle;
    }

    public function setScrewIe8($screwIe8)
    {
        $this->screwIe8 = $screwIe8;
    }

    public function setComments($comments)
    {
        $this->comments = $comments;
    }

    public function setWrap($wrap)
    {
        $this->wrap = $wrap;
    }

    public function setDefines(array $defines)
    {
        $this->defines = $defines;
    }

    public function filterLoad(AssetInterface $asset)
    {
    }

    public function filterDump(AssetInterface $asset)
    {
        $commandline = $this->nodeBin
                ? array($this->nodeBin, $this->uglifyjsBin)
                : array($this->uglifyjsBin);

        if ($this->compress) {
            array_push($commandline,'--compress');

            if (is_string($this->compress) && !empty($this->compress)) {
                array_push($commandline,$this->compress);
            }
        }

        if ($this->beautify) {
            array_push($commandline,'--beautify');
        }

        if ($this->mangle) {
            array_push($commandline,'--mangle');
        }

        if ($this->screwIe8) {
            array_push($commandline,'--screw-ie8');
        }

        if ($this->comments) {
            array_push($commandline,'--comments', true === $this->comments ? 'all' : $this->comments);
        }

        if ($this->wrap) {
            array_push($commandline,'--wrap',$this->wrap);
        }

        if ($this->defines) {
            array_push($commandline,'--define', implode(',', $this->defines));
        }

        // input and output files
        $input = FilesystemUtils::createTemporaryFile('uglifyjs2_in');
        $output = FilesystemUtils::createTemporaryFile('uglifyjs2_out');

        file_put_contents($input, $asset->getContent());
        array_push($commandline,'-o',$output,$input);

        $proc = new Process(implode(' ', $commandline));
        $code = $proc->run();
        unlink($input);

        if (0 !== $code) {
            if (file_exists($output)) {
                unlink($output);
            }

            if (127 === $code) {
                throw new \RuntimeException('Path to node executable could not be resolved.');
            }

            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        if (!file_exists($output)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $asset->setContent(file_get_contents($output));

        unlink($output);
    }
}
