<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Extension\Twig;

use Assetic\Factory\AssetFactory;
use Assetic\ValueSupplierInterface;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

class AsseticExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var \Assetic\Factory\AssetFactory
     */
    private $factory;

    /**
     * @var array
     */
    private $functions;

    /**
     * @var \Assetic\ValueSupplierInterface
     */
    private $valueSupplier;

    /**
     * @var bool
     */
    private $dynamicRoute = false;

    /**
     * AsseticExtension constructor.
     *
     * @param \Assetic\Factory\AssetFactory        $factory
     * @param array                                $functions
     * @param null|\Assetic\ValueSupplierInterface $valueSupplier
     */
    public function __construct(AssetFactory $factory, $functions = [], ValueSupplierInterface $valueSupplier = null)
    {
        $this->factory       = $factory;
        $this->functions     = [];
        $this->valueSupplier = $valueSupplier;

        foreach ($functions as $function => $options) {
            if (\is_int($function) && \is_string($options)) {
                $this->functions[$options] = ['filter' => $options];
            } else {
                $this->functions[$function] = $options + ['filter' => $function];
            }
        }
    }

    /**
     * @return void
     */
    public function enableDynamicRoute()
    {
        $this->dynamicRoute = true;
    }

    /**
     * @return void
     */
    public function disableDynamicRoute()
    {
        $this->dynamicRoute = false;
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenParsers()
    {
        return [
            new AsseticTokenParser($this->factory, 'javascripts', 'js/*.js'),
            new AsseticTokenParser($this->factory, 'stylesheets', 'css/*.css'),
            new AsseticTokenParser($this->factory, 'image', 'images/*', true),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $functions = [
            new TwigFunction('asset', [$this, 'assetFunction']),
        ];
        foreach ($this->functions as $function => $filter) {
            $functions[] = new TwigFunction($function, null, [
                'needs_environment' => false,
                'needs_context'     => false,
                'node_class'        => '\Assetic\Extension\Twig\AsseticFilterNode',
            ]);
        }

        return $functions;
    }

    /**
     * {@inheritdoc}
     */
    public function getGlobals(): array
    {
        return [
            'assetic' => [
                'debug'         => $this->factory->isDebug(),
                'vars'          => null !== $this->valueSupplier ? new ValueContainer($this->valueSupplier) : [],
                'dynamic.route' => $this->dynamicRoute,
            ],
        ];
    }

    /**
     * @param string $function
     *
     * @return \Assetic\Extension\Twig\AsseticFilterInvoker
     */
    public function getFilterInvoker($function)
    {
        return new AsseticFilterInvoker($this->factory, $this->functions[$function]);
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function assetFunction($url)
    {
        if ($this->isAbsolutePath($url)) {
            return $url;
        }

        return '/' . $url;
    }

    private function isAbsolutePath($path)
    {
        // @formatter:off
        if ('/' === $path[0] || '\\' === $path[0]
            || (\strlen($path) > 3 && \ctype_alpha($path[0]) && ':' === $path[1] && ('\\' === $path[2] || '/' === $path[2]))
            || null !== \parse_url($path, PHP_URL_SCHEME)
        ) {
            return true;
        }
        // @formatter:on

        return false;
    }
}
