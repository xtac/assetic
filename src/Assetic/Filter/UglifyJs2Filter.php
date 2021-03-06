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

/**
 * UglifyJs2 filter.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 *
 * @see http://lisperator.net/uglifyjs
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
        $this->nodeBin     = $nodeBin;
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
        $pb = $this->createProcessBuilder(
            $this->nodeBin
            ? [$this->nodeBin, $this->uglifyjsBin]
            : [$this->uglifyjsBin]
        );

        if ($this->compress) {
            $pb->add('--compress');

            if (\is_string($this->compress) && !empty($this->compress)) {
                $pb->add($this->compress);
            }
        }

        if ($this->beautify) {
            $pb->add('--beautify');
        }

        if ($this->mangle) {
            $pb->add('--mangle');
        }

        if ($this->screwIe8) {
            $pb->add('--screw-ie8');
        }

        if ($this->comments) {
            $pb->add('--comments')->add(true === $this->comments ? 'all' : $this->comments);
        }

        if ($this->wrap) {
            $pb->add('--wrap')->add($this->wrap);
        }

        if ($this->defines) {
            $pb->add('--define')->add(\implode(',', $this->defines));
        }

        // input and output files
        $input  = FilesystemUtils::createTemporaryFile('uglifyjs2_in');
        $output = FilesystemUtils::createTemporaryFile('uglifyjs2_out');

        \file_put_contents($input, $asset->getContent());
        $pb->add('-o')->add($output)->add($input);

        $proc = $pb->getProcess();
        $code = $proc->run();
        \unlink($input);

        if (0 !== $code) {
            if (\file_exists($output)) {
                \unlink($output);
            }

            if (127 === $code) {
                throw new \RuntimeException('Path to node executable could not be resolved.');
            }

            throw FilterException::fromProcess($proc)->setInput($asset->getContent());
        }

        if (!\file_exists($output)) {
            throw new \RuntimeException('Error creating output file.');
        }

        $asset->setContent(\file_get_contents($output));

        \unlink($output);
    }
}
