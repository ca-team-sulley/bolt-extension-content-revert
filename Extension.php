<?php

namespace Bolt\Extension\Cainc\ContentRevert;

use Bolt\BaseExtension;
use Bolt\Library;
use Bolt\Translation\Translator as Trans;
use Silex\Application;

class Extension extends BaseExtension
{
    /**
     * Initializes the extension
     */
    public function initialize()
    {
        // No need to initialize if not in a backend context
        if ($this->app['config']->getWhichEnd() !== 'backend') {
            return;
        }

        // Register the service provider
        $this->app->register(new ServiceProvider());

        // Bind routes to the app
        $this->bindRoutes();

        // Override the default twig files with the ones under templates
        $this->app['twig.loader.filesystem']->prependPath(__DIR__ . '/templates');
    }

    /**
     * Get the extension name
     *
     * @return string The extension name
     */
    public function getName()
    {
        return "ContentRevert";
    }

    /**
     * Reversion controller
     *
     * @param string      $contenttype The content type slug
     * @param int         $contentid   The content ID
     * @param int         $id          The changelog entry ID
     * @param Application $app         The application/container
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse Redirect to the edit content page
     */
    public function changelogRevert($contenttype, $contentid, $id, Application $app)
    {
        // Deny access if the user does not have changelog details permissions
        if (!$app['users']->isAllowed('changelogrecordsingle')) {
            $app->abort(401, 'Not Allowed');
        }

        try {
            $app['reversion']->revertChange($contenttype, $contentid, $id, $this->config['skip_hidden_fields']);
            $app['session']->getFlashBag()->add('success', Trans::__("The record has been reverted to a previous state."));
        } catch (\RuntimeException $e) {
            $app['session']->getFlashBag()->add('error', Trans::__("The requested changelog entry doesn't exist."));
        }

        // Redirect the user to the record's edit content page
        return Library::redirect('editcontent', [
            'contenttypeslug' => $contenttype,
            'id' => $contentid,
        ]);
    }

    /**
     * Bind our routes to the app
     */
    private function bindRoutes()
    {
        // Ensure we use the correct admin mount
        $basePath = $this->app['config']->get('general/branding/path', '/bolt');

        // Upon a get request with the following url structure, call changelogRevert
        $this->app->get($basePath . '/changelog/{contenttype}/{contentid}/{id}/revert', [$this, 'changelogRevert'])
            ->assert('id', '\d*')
            ->bind('changelogRevert');
    }
}
