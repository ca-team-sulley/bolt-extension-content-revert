<?php

namespace Bolt\Extension\Cainc\ContentRevert\Tests;

use Bolt\Content;
use Bolt\Extension\Cainc\ContentRevert\Reversion;
use Bolt\Logger\ChangeLog;
use Bolt\Logger\ChangeLogItem;
use Bolt\Storage;
use Mockery as m;

class ReversionTest extends \PHPUnit_Framework_TestCase
{
    private $mockChangeLog;
    private $mockStorage;
    private $service;

    public function setUp()
    {
        $this->mockChangeLog = m::mock(ChangeLog::class);
        $this->mockStorage = m::mock(Storage::class);
        $this->service = new Reversion($this->mockChangeLog, $this->mockStorage);
    }

    public function testRevertChangeCompletes()
    {
        $this->mockChangeLog
            ->shouldReceive('getChangelogEntry')
            ->andReturn(m::mock(ChangeLogItem::class));

        $this->mockStorage
            ->shouldReceive('getContent')
            ->andReturn(m::mock(Content::class));

        $this->mockStorage
            ->shouldReceive('saveContent')
            ->once();

        $this->service->revertChange('testtype', 123, 456);
    }

    public function testRevertChangeInvalidChangelog()
    {
        $this->setExpectedException('RuntimeException');

        $this->mockChangeLog
            ->shouldReceive('getChangelogEntry')
            ->andReturn(false);

        $this->mockStorage
            ->shouldReceive('saveContent')
            ->never();

        $this->service->revertChange('testtype', 123, 456);
    }
}
