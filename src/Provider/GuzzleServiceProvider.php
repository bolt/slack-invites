<?php

namespace Bolt\Site\SlackInvites\Provider;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Guzzle service provider.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class GuzzleServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['guzzle.base_url'] = '/';

        if (!isset($pimple['guzzle.handler_stack'])) {
            $pimple['guzzle.handler_stack'] = function () {
                return HandlerStack::create();
            };
        }

        $pimple['guzzle'] = function ($pimple) {
            $options = [
                'base_uri' => $pimple['guzzle.base_url'],
                'handler'  => $pimple['guzzle.handler_stack'],
            ];
            $client = new Client($options);

            return $client;
        };
    }
}
