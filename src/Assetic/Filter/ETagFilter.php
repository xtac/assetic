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

/**
 * @author Seungwoo Yuk <extacy@appwhole.co.kr>
 */
class ETagFilter implements HashableInterface, FilterInterface
{
    /**
     * @var string
     */
    private $etag;

    /**
     * ETagFilter constructor.
     *
     * @param string $etag
     */
    public function __construct($etag)
    {
        $this->etag = $etag;
    }

    /**
     * {@inheritdoc}
     */
    public function hash()
    {
        return $this->etag;
    }

    /**
     * {@inheritdoc}
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function filterDump(AssetInterface $asset)
    {
    }
}
