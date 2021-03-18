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

use Drill\Framework\Foundation\ContainerBuilder\Argument\Reference;
use Drill\Framework\Foundation\ContainerBuilder\CompileProcessorInterface;
use Drill\Framework\Foundation\ContainerBuilder\Definitions;

/**
 * @author Seungwoo Yuk <extacy@perfect-storm.net>
 */
class AsseticProcessor implements CompileProcessorInterface
{
    public function process(Definitions $definitions): void
    {
        $this->processWorker($definitions);
        $this->processFilterManager($definitions);
        $this->processTwigLoader($definitions);
        $this->processAssetWriter($definitions);
    }

    private function processWorker(Definitions $definitions)
    {
        if (!$definitions->has('assetic.factory')) {
            return;
        }

        $factory = $definitions->get('assetic.factory');

        foreach ($definitions->getTaggedDefinitions('assetic.worker') as $definition => $attrs) {
            $factory->addMethodCall('addWorker', new Reference($definition->getId()));
        }
    }

    private function processFilterManager(Definitions $definitions)
    {
        if (!$definitions->has('assetic.factory')) {
            return;
        }

        if (!$definitions->has('assetic.filter_manager')) {
            return;
        }

        $def = $definitions->get('assetic.filter_manager');
        if ($def->getClass() && (FilterManager::class === $def->getClass() || \is_subclass_of($def->getClass(), FilterManager::class))) {
            foreach ($definitions->getTaggedDefinitions('assetic.filter_manager') as $definition => $attrs) {
                if (!isset($attrs[0]['alias'])) {
                    continue;
                }

                $def->addMethodCall('set', $attrs[0]['alias'], new Reference($definition->getId()));
            }

            $definitions->get('assetic.factory')->addMethodCall('setFilterManager', new Reference('assetic.filter_manager'));
        }
    }

    private function processTwigLoader(Definitions $definitions)
    {
        if (!$definitions->has('assetic.dumper')) {
            return;
        }

        if ($definitions->has('twig.loader.filesystem')) {
            $definitions->get('assetic.dumper')->addMethodCall('setTwigLoader', new Reference('twig.loader.filesystem'));
        }
    }

    private function processAssetWriter(Definitions $definitions)
    {
        if (!$definitions->has('assetic.asset_writer')) {
            return;
        }

        // assetic.value_supplier 에 제공 되는 키에 대한 값을 배열로 선언한다.
        // 문자열로만 설정 가능하다.
        $variables = $definitions->hasBuildParameter('assetic.variables') ? $definitions->getBuildParameter('assetic.variables') : [];

        if (!isset($variables['locale'])) {
            $variables['locale'] = $definitions->hasBuildParameter('i18n.available_locales') ? \array_keys($definitions->getBuildParameter('i18n.available_locales')) : [];
        }

        if (!isset($variables['debug'])) {
            $variables['debug'] = ['true', 'false'];
        }

        $definitions->get('assetic.asset_writer')->replaceArgument(1, $variables);
    }
}
