<?php
/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Factory\Worker;

use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;

/**
 * @author Seungwoo Yuk <extacy@perfect-storm.net>
 */
class AsseticTargetPathWorker implements WorkerInterface
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * AsseticTargetPathWorker constructor.
     *
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = \rtrim($prefix, '/') . '/';
    }

    /**
     * {@inheritdoc}
     */
    public function process(AssetInterface $asset, AssetFactory $factory)
    {
        if (!$path = $asset->getTargetPath()) {
            return;
        }

        if (0 === \strpos($path, $this->prefix)) {
            return;
        }

        $asset->setTargetPath($this->prefix . $path);
    }
}
