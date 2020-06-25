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

use Assetic\Console\TwigAsseticDumpCommand;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\LazyAssetManager;
use Assetic\Factory\Worker\AsseticTargetPathWorker;
use Drill\Framework\Foundation\DefaultParametersProviderInterface;
use Drill\Framework\Resource\Locator\NamespacedResourceLocator;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * @author Seungwoo Yuk <extacy@perfect-storm.net>
 */
class AsseticServiceProvider implements ServiceProviderInterface, DefaultParametersProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDefaultParameters(Container $app): array
    {
        return [
            'assetic.directory'   => 'assets',
            'assetic.public_path' => $app['path.web'] ?? $app['path.user'],
            'assetic.options'     => [
                'debug'         => $app['debug'],
            ],
            'assetic.filter_manager' => null,
            'assetic.resource_dirs'  => [],  // for legacy - @namespace/path...
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        // assetic.value_supplier 에거 제공 되는 키에 대한 값을 배열로 선언한다.
        // 문자열로만 설정 가능하다.
        $app['assetic.variables']      = function ($app) {
            return [
                'locale' => isset($app['i18n.translator']) ? \array_keys($app['i18n.translator']->getAvailableLocales()) : [],
                'debug'  => ['true', 'false'],
            ];
        };
        $app['assetic.value_supplier'] = function ($app) {
            return new DefaultValueSupplier($app);
        };

        $app['assetic.factory'] = function ($app) {
            if (class_exists(NamespacedResourceLocator::class)) {
                $locator = !empty($app['assetic.resource_dirs']) ? new NamespacedResourceLocator($app['assetic.resource_dirs']) : null;
            } else {
                $locator = $app['module.resource.locator'] ?? null;
            }

            $factory = new AssetFactory($app, $app['assetic.public_path'], $locator, $app['assetic.options']['debug']);

            if (isset($app['assetic.filter_manager']) && $app['assetic.filter_manager'] instanceof FilterManager) {
                $factory->setFilterManager($app['assetic.filter_manager']);
            }
            $factory->addWorker(new AsseticTargetPathWorker($app['assetic.directory']));

            return $factory;
        };

        $app['assetic.asset_writer'] = function ($app) {
            return new AssetWriter($app['assetic.public_path'], $app['assetic.variables']);
        };

        $app['assetic.dumper'] = function ($app) {
            $dumper = new FileDumper($app['assetic.asset_manager'], $app['assetic.asset_writer'], $app['assetic.options']['debug']);

            if (isset($app['twig.loader.filesystem'])) {
                $dumper->setTwigLoader($app['twig.loader.filesystem']);
            }

            return $dumper;
        };

        $app['assetic.asset_manager'] = function ($app) {
            $lazy = new LazyAssetmanager($app['assetic.factory']);

            if (isset($app['twig'])) {
                $lazy->setLoader('twig', new TwigFormulaLoader($app['twig']));
            }

            return $lazy;
        };

        $app['twig.extension.assetic'] = function ($app) {
            return new AsseticExtension($app['assetic.factory'], [], $app['assetic.value_supplier']);
        };

        $app['assetic.command.twig_assets_dump'] = function () {
            return new TwigAsseticDumpCommand();
        };

        $app['tagger']->add('twig.extension', 'twig.extension.assetic');
        $app['tagger']->add('console.command', 'assetic.command.twig_assets_dump');
    }
}
