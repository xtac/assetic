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
use Drill\Framework\Foundation\ContainerBuilder\Argument\CompileReference;
use Drill\Framework\Foundation\ContainerBuilder\Argument\Reference;
use Drill\Framework\Foundation\ContainerBuilder\Builder;
use Drill\Framework\Foundation\ContainerBuilder\BuilderConfiguratorInterface;
use Drill\Framework\Foundation\ContainerBuilder\DefinitionProviderInterface;
use Drill\Framework\Foundation\ContainerBuilder\Definitions;
use Drill\Framework\Resource\Locator\NamespacedResourceLocator;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * @author Seungwoo Yuk <extacy@perfect-storm.net>
 */
class AsseticServiceProvider implements ServiceProviderInterface, DefinitionProviderInterface, BuilderConfiguratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function register(Container $app)
    {
        $app['assetic.directory']   = 'assets';
        $app['assetic.public_path'] = $app['path.web'] ?? $app['path.user'];
        $app['assetic.options']     = [
            'debug'         => $app['debug'],
        ];
        $app['assetic.resource_dirs']  = [];
    }

    public function registerDefinitions(Container $app, Definitions $definitions): void
    {
        $definitions->create('assetic.value_supplier', DefaultValueSupplier::class)
            ->setArguments(new Reference('@'));

        $definitions->create('assetic.worker.target_path', AsseticTargetPathWorker::class)
            ->setArguments($app['assetic.directory'])
            ->addTag('assetic.worker');

        if (!empty($app['assetic.resource_dirs'])) {
            $definitions->create('assetic.resource_locator', NamespacedResourceLocator::class)
                ->setArguments($app['assetic.resource_dirs']);

            $locator = new Reference('assetic.resource_locator');
        } else {
            $locator = null;
        }

        $definitions->create('assetic.factory', AssetFactory::class)
            ->setArguments(new Reference('@'), $app['assetic.public_path'], $locator, $app['assetic.options']['debug']);

        $definitions->create('assetic.filter_manager', FilterManager::class);

        $definitions->create('assetic.asset_writer', AssetWriter::class)
            ->setArguments($app['assetic.public_path'], new CompileReference('assetic.variables'));

        $definitions->create('assetic.dumper', FileDumper::class)
            ->setArguments(new Reference('assetic.asset_manager'), new Reference('assetic.asset_writer'), $app['assetic.options']['debug']);

        $definitions->create('assetic.twig_formula_loader', TwigFormulaLoader::class)
            ->setArguments(new Reference('twig'));

        $definitions->create('assetic.asset_manager', LazyAssetManager::class)
            ->setArguments(new Reference('assetic.factory'))
            ->addMethodCall('setLoader', 'twig', new Reference('assetic.twig_formula_loader'));

        $definitions->create('twig.extension.assetic', AsseticExtension::class)
            ->setArguments(new Reference('assetic.factory'), [], new Reference('assetic.value_supplier'))
            ->addTag('twig.extension');

        $definitions->create('assetic.command.twig_assets_dump', TwigAsseticDumpCommand::class)
            ->addTag('console.command');

        $definitions->setBuildParameter('assetic.variables', $app['assetic.variables'] ?? []);
    }

    public function configBuilder(Builder $builder): void
    {
        $builder->addCompileProcessor(new AsseticProcessor());
    }
}
