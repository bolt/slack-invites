<?php

namespace Bolt\Site\SlackInvites\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex;
use Twig\Loader\FilesystemLoader;

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

        $pimple['twig.loader.filesystem'] = $pimple->extend(
            'twig.loader.filesystem',
            function (FilesystemLoader $loader) {
                $loader->addPath(__DIR__ . '/../../templates', 'templates');

                return $loader;
            }
        );

        $pimple['twig.options'] = [
            'cache' => __DIR__ . '/../../var/cache/twig',
        ];
    }
}
