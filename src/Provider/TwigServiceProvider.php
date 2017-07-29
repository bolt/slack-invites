<?php

namespace Bolt\Site\SlackInvites\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex;

/**
 * Twig service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class TwigServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple->register(new Silex\Provider\TwigServiceProvider());

        $pimple['twig.path'] = [
            __DIR__ . '/../../templates',
        ];
        $pimple['twig.options'] = [
            'cache' => __DIR__ . '/../../var/cache/twig',
        ];
    }
}
