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
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Node\Expression\FunctionExpression;
use Twig\Node\Expression\GetAttrExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;
use Twig\Template;

class AsseticNode extends Node
{
    /**
     * Constructor.
     *
     * Available attributes:
     *
     *  * debug:    The debug mode
     *  * combine:  Whether to combine assets
     *  * var_name: The name of the variable to expose to the body node
     *
     * @param \Assetic\Asset\AssetInterface $asset      The asset
     * @param \Twig\Node\Node               $body       The body node
     * @param array                         $inputs     An array of input strings
     * @param array                         $filters    An array of filter strings
     * @param string                        $name       The name of the asset
     * @param array                         $attributes An array of attributes
     * @param int                           $lineno     The line number
     * @param string                        $tag        The tag name
     */
    public function __construct(AssetInterface $asset, Node $body, array $inputs, array $filters, string $name, array $attributes = [], int $lineno = 0, string $tag = null)
    {
        $nodes = ['body' => $body];

        $attributes = \array_replace(
            ['debug' => null, 'combine' => null, 'var_name' => 'asset_url'],
            $attributes,
            ['asset' => $asset, 'inputs' => $inputs, 'filters' => $filters, 'name' => $name]
        );

        parent::__construct($nodes, $attributes, $lineno, $tag);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->addDebugInfo($this);

        $combine = $this->getAttribute('combine');
        $debug   = $this->getAttribute('debug');

        if (null === $combine && null !== $debug) {
            $combine = !$debug;
        }

        if (null === $combine) {
            $compiler
                ->write("if (isset(\$context['assetic']['debug']) && \$context['assetic']['debug']) {\n")
                ->indent();

            $this->compileDebug($compiler);

            $compiler
                ->outdent()
                ->write("} else {\n")
                ->indent();

            $this->compileAsset($compiler, $this->getAttribute('asset'), $this->getAttribute('name'));

            $compiler
                ->outdent()
                ->write("}\n");
        } elseif ($combine) {
            $this->compileAsset($compiler, $this->getAttribute('asset'), $this->getAttribute('name'));
        } else {
            $this->compileDebug($compiler);
        }

        $compiler
            ->write('unset($context[')
            ->repr($this->getAttribute('var_name'))
            ->raw("]);\n");
    }

    protected function compileDebug(Compiler $compiler)
    {
        $i = 0;
        foreach ($this->getAttribute('asset') as $leaf) {
            $leafName = $this->getAttribute('name') . '_' . $i++;
            $this->compileAsset($compiler, $leaf, $leafName);
        }
    }

    protected function compileAsset(Compiler $compiler, AssetInterface $asset, $name)
    {
        if ($vars = $asset->getVars()) {
            $compiler->write("// check variable conditions\n");

            foreach ($vars as $var) {
                $compiler
                    ->write("if (!isset(\$context['assetic']['vars']['$var'])) {\n")
                    ->indent()
                    ->write("throw new \RuntimeException(sprintf('The asset \"" . $name . '" expected variable "' . $var . "\" to be set, but got only these vars: %s. Did you set-up a value supplier?', isset(\$context['assetic']['vars']) && \$context['assetic']['vars'] ? implode(', ', \$context['assetic']['vars']) : '# none #'));\n")
                    ->outdent()
                    ->write("}\n");
            }

            $compiler->raw("\n");
        }

        $compiler
            ->write("// asset \"$name\"\n")
            ->write('$context[')
            ->repr($this->getAttribute('var_name'))
            ->raw('] = ');

        $this->compileAssetUrl($compiler, $asset, $name);

        $compiler
            ->raw(";\n")
            ->subcompile($this->getNode('body'));
    }

    protected function originCompileAssetUrl(Compiler $compiler, AssetInterface $asset, $name)
    {
        if (!$vars = $asset->getVars()) {
            $compiler->repr($asset->getTargetPath());

            return;
        }

        $compiler
            ->raw('strtr(')
            ->string($asset->getTargetPath())
            ->raw(', array(');

        $first = true;
        foreach ($vars as $var) {
            if (!$first) {
                $compiler->raw(', ');
            }
            $first = false;

            $compiler
                ->string('{' . $var . '}')
                ->raw(" => \$context['assetic']['vars']['$var']");
        }

        $compiler
            ->raw('))');
    }

    /**
     * {@inheritdoc}
     */
    protected function compileAssetUrl(Compiler $compiler, AssetInterface $asset, $name)
    {
        $vars = [];
        foreach ($asset->getVars() as $var) {
            $vars[] = new ConstantExpression($var, $this->getTemplateLine());

            // Retrieves values of assetic vars from the context, $context['assetic']['vars'][$var].
            /** @noinspection PhpInternalEntityUsedInspection */
            $vars[] = new GetAttrExpression(
                new GetAttrExpression(
                    new NameExpression('assetic', $this->getTemplateLine()),
                    new ConstantExpression('vars', $this->getTemplateLine()),
                    new ArrayExpression([], $this->getTemplateLine()),
                    Template::ARRAY_CALL,
                    $this->getTemplateLine()
                ),
                new ConstantExpression($var, $this->getTemplateLine()),
                new ArrayExpression([], $this->getTemplateLine()),
                Template::ARRAY_CALL,
                $this->getTemplateLine()
            );
        }

        $compiler->raw('isset($context[\'assetic\'][\'dynamic.route\']) && $context[\'assetic\'][\'dynamic.route\'] ? ');
        $compiler->subcompile($this->getPathFunction($name, $vars));
        $compiler->raw(' : ');
        $compiler->subcompile($this->getAssetFunction(new AsseticTargetPathNode($this, $asset, $name)));
    }

    private function getPathFunction($name, array $vars = [])
    {
        $nodes = [new ConstantExpression('_assetic_' . $name, $this->getTemplateLine())];

        if (!empty($vars)) {
            $nodes[] = new ArrayExpression($vars, $this->getTemplateLine());
        }

        return new FunctionExpression('path', new Node($nodes), $this->getTemplateLine());
    }

    private function getAssetFunction($path)
    {
        $arguments = [$path];

        return new FunctionExpression('asset', new Node($arguments), $this->getTemplateLine());
    }
}
