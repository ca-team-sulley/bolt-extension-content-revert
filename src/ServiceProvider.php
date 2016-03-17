<?php

namespace Bolt\Extension\Cainc\ContentRevert;

use Silex\Application;
use Silex\ServiceProviderInterface;

class ServiceProvider implements ServiceProviderInterface
{
    /**
     * Registers the reversion service
     */
    public function register(Application $app)
    {
        $app['reversion'] = $app->share(function ($app) {
            return new Reversion($app['logger.manager.change'], $app['storage']);
        });
    }

    /**
     * Bootstraps the application
     */
    public function boot(Application $app)
    {
    }
}
