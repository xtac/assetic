<?php
/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic;

use Assetic\Asset\AssetInterface;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Factory\LazyAssetManager;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Twig\Loader\FilesystemLoader;

/**
 * @author Seungwoo Yuk <extacy@appwhole.co.kr>
 */
class FileDumper
{
    /**
     * @var \Assetic\Factory\LazyAssetManager
     */
    protected $lam;

    /**
     * @var \Assetic\AssetWriter
     */
    protected $writer;

    /**
     * @var \Twig\Loader\FilesystemLoader
     */
    protected $loader;

    /**
     * @var bool
     */
    protected $loading = false;

    /**
     * @var bool
     */
    protected $loaded = false;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * FileDumper constructor.
     *
     * @param \Assetic\Factory\LazyAssetManager $lam
     * @param \Assetic\AssetWriter              $writer
     * @param bool                              $debug
     */
    public function __construct(LazyAssetManager $lam, AssetWriter $writer, $debug = false)
    {
        $this->lam    = $lam;
        $this->writer = $writer;
        $this->debug  = $debug;
    }

    /**
     * @param \Twig\Loader\FilesystemLoader $loader
     *
     * @return void
     */
    public function setTwigLoader(FilesystemLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @return void
     */
    public function clearAssetManagers()
    {
        if (!$this->loaded) {
            $this->addLazyAssetManagerResources();
        }

        $this->getAssetManager()->clear();
    }

    /**
     * @return \Assetic\Factory\LazyAssetManager
     */
    public function getAssetManager()
    {
        if (!$this->loaded) {
            $this->addLazyAssetManagerResources();
        }

        return $this->lam;
    }

    /**
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public function dump(OutputInterface $output = null)
    {
        if (!$this->loaded) {
            $this->addLazyAssetManagerResources();
        }

        $manager = $this->getAssetManager();

        foreach ($manager->getNames() as $name) {
            $this->dumpAsset($name, $manager, $output);
        }
    }

    /**
     * @param string                                                 $name
     * @param \Assetic\Factory\LazyAssetManager                      $manager
     * @param null|\Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    public function dumpAsset($name, LazyAssetManager $manager, OutputInterface $output = null)
    {
        if (!$this->loaded) {
            $this->addLazyAssetManagerResources();
        }

        $asset = $manager->get($name);
        $this->writeAsset($asset, $output);

        if ($manager instanceof LazyAssetManager && $manager->hasFormula($name)) {
            $formula = $manager->getFormula($name);
        } else {
            $formula = [];
        }

        $debug   = $formula[2]['debug'] ?? $manager->isDebug();
        $combine = $formula[2]['combine'] ?? !$debug;

        if (!$combine) {
            foreach ($asset as $leaf) {
                $this->writeAsset($leaf, $output);
            }
        }
    }

    /**
     * @return void
     */
    protected function addLazyAssetManagerResources()
    {
        if ($this->loading) {
            return;
        }

        $this->loading = true;

        $filesystem     = new Filesystem();
        $twigNamespaces = $this->loader->getNamespaces();

        foreach ($twigNamespaces as $ns) {
            if (\count($paths = $this->loader->getPaths($ns)) > 0) {
                $iterator = Finder::create()->files()->followLinks()->in($paths)->name('*.twig');
                /** @var \Symfony\Component\Finder\SplFileInfo $file */
                foreach ($iterator as $file) {
                    if ($this->debug) {
                        $filesystem->touch($file->getRealPath(), 0);
                    }

                    $resource = new TwigResource($this->loader, '@' . $ns . '/' . $file->getRelativePathname());
                    $this->lam->addResource($resource, 'twig');
                }
            }
        }

        $this->loaded  = true;
        $this->loading = false;
    }

    /**
     * @param \Assetic\Asset\AssetInterface                     $asset
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    protected function writeAsset(AssetInterface $asset, OutputInterface $output = null)
    {
        $this->output(\sprintf(' * Dumps asset to <info>%s</info>', $asset->getTargetPath()), $output);

        $this->writer->writeAsset($asset);
    }

    protected function output($message, OutputInterface $output = null)
    {
        if (null === $output || empty($message)) {
            return;
        }

        $output->writeln($message);
    }
}
