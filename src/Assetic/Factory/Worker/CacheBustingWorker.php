<?php
/**
 * This file is part of the gundolle.com package.
 *
 * (c) Perfect storm dev team <dev_all@perfect-storm.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Factory\Worker;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;

/**
 * Adds cache busting code
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CacheBustingWorker implements WorkerInterface
{
    /**
     * @var array
     */
    private $hashes = [];

    /**
     * @var array
     */
    private $options;

    public function __construct(array $options = [])
    {
        $this->options = \array_replace(
            [
                'separator'   => '.',
                'hash_algo'   => 'sha1',
                'hash_length' => 12,
            ],
            $options
        );
    }

    public function process(AssetInterface $asset, AssetFactory $factory)
    {
        if (!$path = $asset->getTargetPath()) {
            // no path to work with
            return;
        }

        if (!$search = \pathinfo($path, PATHINFO_EXTENSION)) {
            // nothing to replace
            return;
        }

        $replace = $this->options['separator'] . $this->getHash($asset, $factory) . '.' . $search;
        if (\preg_match('/' . \preg_quote($replace, '/') . '$/', $path)) {
            // already replaced
            return;
        }

        $asset->setTargetPath(
            \preg_replace('/\.' . \preg_quote($search, '/') . '$/', $replace, $path)
        );
    }

    protected function getHash(AssetInterface $asset, AssetFactory $factory)
    {
        $context = \hash_init($this->options['hash_algo']);

        if ($asset instanceof AssetCollectionInterface) {
            foreach ($asset as $i => $leaf) {
                $this->updateAssetHash($leaf, $context);
            }
        } else {
            $this->updateAssetHash($asset, $context);
        }

        return \substr(\hash_final($context), 0, $this->options['hash_length']);
    }

    protected function updateAssetHash(AssetInterface $asset, $context)
    {
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();

        if ($sourceRoot && $sourcePath && \file_exists($source = $sourceRoot . '/' . $sourcePath)) {
            if (!isset($this->hashes[$source])) {
                $this->hashes[$source] = \hash_file($this->options['hash_algo'], $source);
            }

            $data = $this->hashes[$source];
        } else {
            $stack = [
                $sourceRoot,
                $sourcePath,
                $asset->getTargetPath(),
            ];
            $data  = \implode('|', $stack);
        }

        \hash_update($context, $data);
    }
}
