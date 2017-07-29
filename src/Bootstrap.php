<?php

namespace Bolt\Site\SlackInvites;

use Bolt\Site\SlackInvites\Controller\Invites;
use Bolt\Site\SlackInvites\Provider\AssetServiceProvider;
use Bolt\Site\SlackInvites\Provider\CacheServiceProvider;
use Bolt\Site\SlackInvites\Provider\GuzzleServiceProvider;
use Bolt\Site\SlackInvites\Provider\SlackServiceProvider;
use Bolt\Site\SlackInvites\Provider\TwigServiceProvider;
use Silex\Application;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\MonologServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\WebProfilerServiceProvider;

/**
 * Application bootstrap.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Bootstrap
{
    /**
     * Create the application.
     *
     * @return \Silex\Application
     */
    public static function run($debug = false)
    {
        $app = new Application();

        $app->register(new ServiceControllerServiceProvider());
        $app->register(new AssetServiceProvider());
        $app->register(new TwigServiceProvider());
        $app->register(new HttpFragmentServiceProvider());
        $app->register(new ValidatorServiceProvider());
        $app->register(new CacheServiceProvider());
        $app->register(new GuzzleServiceProvider());
        $app->register(new SlackServiceProvider());

        $app->mount('/', new Invites());

        if ($debug) {
            static::enableDebug($app);
        }
        if (!file_exists(__DIR__ . '/../config/slack.php')) {
            throw new \LogicException('Unable to find ' . __DIR__ . '/../config/slack.php');
        }
        require_once __DIR__ . '/../config/slack.php';

        return $app;
    }

    /**
     * Register debugging services.
     *
     * @param Application $app
     */
    private static function enableDebug(Application $app)
    {
        $app['debug'] = true;

        $app->register(new MonologServiceProvider(), [
            'monolog.logfile' => __DIR__ . '/../var/logs/silex_dev.log',
        ]);

        $app->register(new WebProfilerServiceProvider(), [
            'profiler.cache_dir' => __DIR__ . '/../var/cache/profiler',
        ]);
    }
}
