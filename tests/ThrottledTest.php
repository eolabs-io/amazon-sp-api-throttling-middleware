<?php

namespace EolabsIo\AmazonSpApiThrottlingMiddleware\Tests;

use Mockery;
use Illuminate\Support\Carbon;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Throttled;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Tests\TestCase;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Contracts\Deferrer;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Tests\TestDeferrer;

class ThrottledTest extends TestCase
{
    /** @var \Closure */
    private $next;

    /** @var \Mockery\Mock */
    private $job;

    /** @var Carbon */
    private $knownDate;

    /** @var TestDeferrer */
    private $testDeferrer;

    /** @var Deferrer */
    private $deferrer;

    /** @var Throttled */
    private $middleware;


    protected function setUp(): void
    {
        parent::setUp();

        $this->mockJob();

        $this->knownDate = Carbon::create(2020, 3, 24, 12);
        Carbon::setTestNow(function () { return $this->knownDate->copy(); });

        $this->deferrer = new TestDeferrer();

        $this->middleware = (new Throttled())->key('test-throttle-key')->deferrer($this->deferrer)->maximumQuota(30)->restoreRate(2);
    }

    /** @test */
    public function it_limits_job_execution_tt()
    {
        $this->job->shouldReceive('fire')->times(32);

        $this->assertEquals(0, $this->deferrer->getCurrentTime());

        foreach (range(1, 32) as $i) {
            $this->middleware->handle($this->job, $this->next);
        }

        $this->assertEquals(1000, $this->deferrer->getCurrentTime());
    }

    /** @test */
    public function it_restores_job_execution_afer_throttle()
    {
        $this->job->shouldReceive('fire')->times(62);

        $this->assertEquals(0, $this->deferrer->getCurrentTime());

        foreach (range(1, 31) as $i) {
            $this->middleware->handle($this->job, $this->next);
        }

        $this->assertEquals(500, $this->deferrer->getCurrentTime());
        $this->deferrer->sleep(-500);

        //wait for restore
        $this->knownDate->addSeconds(20);

        foreach (range(1, 31) as $i) {
            $this->middleware->handle($this->job, $this->next);
        }

        $this->assertEquals(500, $this->deferrer->getCurrentTime());
    }

    private function mockJob(): void
    {
        $this->job = Mockery::mock();

        $this->next = function ($job) {
            $job->fire();
        };
    }
}
