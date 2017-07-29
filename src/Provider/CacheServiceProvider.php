<?php

namespace Bolt\Site\SlackInvites\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\Cache\Simple\FilesystemCache;

/**
 * Cache service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CacheServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['cache'] = function ($pimple) {
            if ($pimple['debug']) {
                return new ArrayCache();
            }

            return new FilesystemCache('slack', 0, __DIR__ . '/../../var/cache/data');
        };
    }
}
