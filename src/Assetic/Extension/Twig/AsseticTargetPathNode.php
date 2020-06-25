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

use Assetic\Asset\AssetInterface;
use Twig\Compiler;
use Twig\Node\Node;

/**
 * @author Seungwoo Yuk <extacy@appwhole.co.kr>
 */
class AsseticTargetPathNode extends AsseticNode
{
    /**
     * @var \Drill\Framework\Asset\Twig\Assetic\AsseticNode
     */
    private $node;

    /**
     * @var \Assetic\Asset\AssetInterface
     */
    private $asset;

    /**
     * @var string
     */
    private $name;

    /**
     * AsseticTargetPathNode constructor.
     *
     * @param \Assetic\Extension\Twig\AsseticNode $node
     * @param \Assetic\Asset\AssetInterface       $asset
     * @param                                     $name
     */
    public function __construct(AsseticNode $node, AssetInterface $asset, $name)
    {
        /** @noinspection UnusedConstructorDependenciesInspection */
        $this->node  = $node;
        $this->asset = $asset;
        $this->name  = $name;

        $this->lineno = 0;
    }

    /**
     * {@inheritdoc}
     */
    public function compile(Compiler $compiler)
    {
        $this->originCompileAssetUrl($compiler, $this->asset, $this->name);
    }
}
