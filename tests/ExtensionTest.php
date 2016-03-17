<?php

namespace Bolt\Extension\Cainc\ContentRevert\Tests;

use Bolt\Application;
use Bolt\BaseExtension;
use Bolt\Extension\Cainc\ContentRevert\Extension;
use Bolt\Extension\Cainc\ContentRevert\Reversion;
use Bolt\Tests\BoltUnitTest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MockReversion extends Reversion
{
    public $shouldThrow = false;
    public $lastCall = [];

    public function __construct()
    {
    }

    public function revertChange($contenttype, $contentid, $id, $skipHiddenFields = false)
    {
        $this->lastCall = compact('contenttype', 'contentid', 'id', 'skipHiddenFields');

        if ($this->shouldThrow) {
            throw new \RuntimeException();
        }
    }
}

class ExtensionTest extends BoltUnitTest
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var Extension
     */
    private $extension;

    public function setUp()
    {
        //$this->resetDb();
        $this->app = $this->getApp(true);
        $this->extension = new Extension($this->app);
        $this->app['integritychecker']->repairTables();
    }

    public function testExtensionBasics()
    {
        $this->assertInstanceOf(BaseExtension::class, $this->extension);
        $this->assertEquals('ContentRevert', $this->extension->getName());
    }

    public function testInitializeNotBackend()
    {
        $this->initialize(Request::create('/'));
        $this->assertFalse($this->app->offsetExists('reversion'), 'Reversion service should only be provided to the backend');
    }

    public function testInitializeInBackend()
    {
        $this->initialize();
        $this->assertTrue($this->app->offsetExists('reversion'), 'Reversion service should be provided to the backend');
    }

    public function testControllerInvalidUser()
    {
        $this->initialize();
        $this->app['reversion'] = new MockReversion();
        $this->setExpectedException(HttpException::class);

        $this->app['extensions.ContentRevert']->changelogRevert('testtype', 123, 456, $this->app);
    }

    public function testControllerValidUserInvalidContent()
    {
        $this->initialize();
        $this->app['reversion'] = new MockReversion();
        $this->app['session']->getFlashBag()->set('error', []);
        $this->app['session']->getFlashBag()->set('success', []);
        $this->app['reversion']->shouldThrow = true;
        $this->allowLogin($this->app);

        $response = $this->app['extensions.ContentRevert']->changelogRevert('testtype', 123, 456, $this->app);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertNotEmpty($this->app['session']->getFlashBag()->get('error'), 'An error message should be flashed when content is invalid');
        $this->assertEmpty($this->app['session']->getFlashBag()->get('success'), 'No success message should be flashed when content is invalid');
        $this->assertEquals('testtype', $this->app['reversion']->lastCall['contenttype']);
        $this->assertEquals(123, $this->app['reversion']->lastCall['contentid']);
        $this->assertEquals(456, $this->app['reversion']->lastCall['id']);
        $this->assertEquals(false, $this->app['reversion']->lastCall['skipHiddenFields']);
    }

    public function testControllerCompletes()
    {
        $this->initialize();
        $this->app['reversion'] = new MockReversion();
        $this->app['session']->getFlashBag()->set('error', []);
        $this->app['session']->getFlashBag()->set('success', []);
        $this->allowLogin($this->app);

        $response = $this->app['extensions.ContentRevert']->changelogRevert('testtype', 123, 456, $this->app);

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEmpty($this->app['session']->getFlashBag()->get('error'), 'No error message should be flashed when reversion completes');
        $this->assertNotEmpty($this->app['session']->getFlashBag()->get('success'), 'Success message should be flashed when reversion completes');
        $this->assertEquals('testtype', $this->app['reversion']->lastCall['contenttype']);
        $this->assertEquals(123, $this->app['reversion']->lastCall['contentid']);
        $this->assertEquals(456, $this->app['reversion']->lastCall['id']);
        $this->assertEquals(false, $this->app['reversion']->lastCall['skipHiddenFields']);
    }

    private function initialize(Request $request = null)
    {
        $request = $request ?: Request::create($this->app['config']->get('general/branding/path'));

        $this->app['request'] = $request;
        $this->app['extensions']->register($this->extension);
    }
}
