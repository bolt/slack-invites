<?php

namespace Bolt\Site\SlackInvites\Provider;

use Bolt\Site\SlackInvites\Slack;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Slack service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SlackServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['slack'] = function ($pimple) {
            return new Slack(
                $pimple['guzzle'],
                $pimple['cache'],
                $pimple['slack.team'],
                $pimple['slack.token']
            );
        };
    }
}
