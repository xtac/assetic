<?php
/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2014 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Console;

use Drill\Framework\Console\Command\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Seungwoo Yuk <extacy@appwhole.co.kr>
 */
class TwigAsseticDumpCommand extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('twig:assetic:dump');
        $this->setDescription('Dumps all twig assets.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io  = new SymfonyStyle($input, $output);
        $io->title('Dumping all twig assets.');

        /** @var \Assetic\FileDumper $dumper */
        $dumper  = $this->app('assetic.dumper');
        $manager = $dumper->getAssetManager();

        foreach ($manager->getNames() as $name) {
            $dumper->dumpAsset($name, $manager, $io);
        }

        $io->success('Complete');

        return BaseCommand::SUCCESS;
    }
}
