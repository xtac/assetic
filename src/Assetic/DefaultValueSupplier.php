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

use Pimple\Container;

/**
 * @author Seungwoo Yuk <extacy@appwhole.co.kr>
 */
class DefaultValueSupplier implements ValueSupplierInterface
{
    /**
     * @var \Pimple\Container
     */
    private $container;

    /**
     * DefaultValueSupplier constructor.
     *
     * @param \Pimple\Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return [
            'locale'  => isset($this->container['i18n.translator']) ? $this->container['i18n.translator']->getLocale() : '',
            'debug'   => $this->container['debug'] ? 'true' : 'false',
        ];
    }
}
