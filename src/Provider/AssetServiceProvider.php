<?php

namespace Bolt\Site\SlackInvites\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

/**
 * Asset service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class AssetServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple->register(new Silex\Provider\AssetServiceProvider());

        $pimple['assets.packages'] = $pimple->extend(
            'assets.packages',
            function (Packages $packages) {
                $packages->addPackage('theme', new PathPackage('', new EmptyVersionStrategy()));

                return $packages;
            }
        );
    }
}
