<?php

namespace EolabsIo\AmazonSpApiThrottlingMiddleware\Tests;

use Mockery;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Throttle;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Tests\TestCase;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Contracts\Deferrer;
use EolabsIo\AmazonSpApiThrottlingMiddleware\Tests\TestDeferrer;

class ThrottleTest extends TestCase
{
    /** @var EolabsIo\AmazonSpApiThrottlingMiddleware\Throttle */
    private $throttle;

    /** @var bool */
    private $executedJob = null;

    /** @var int */
    private $throttleDuration = 0;

    /** @var Illuminate\Support\Carbon */
    private $knownDate;

    /** @var EolabsIo\AmazonSpApiThrottlingMiddleware\Tests\TestDeferrer */
    private $testDeferrer;

    /** @var Deferrer */
    private $deferrer;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->knownDate = Carbon::create(2020, 3, 24, 12);
        Carbon::setTestNow(function () { return $this->knownDate->copy(); });

        $this->deferrer = new TestDeferrer();

        $this->throttle = (new Throttle($this->deferrer))->key('throttle-key')
                                                         ->maximumQuota(10)
                                                         ->restoreRate(2);

    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Carbon::setTestNow();
    }

    /** @test */
    public function it_executes_job_without_throttling()
    {
        $this->callThrottle();

        $this->assertWasExecuted();
    }

    /** @test */
    public function it_throttles_job()
    {
        $requestQuota = 10;

        $this->throttle->maximumQuota($requestQuota);

        $this->times($requestQuota, function ($i) {
            $this->callThrottle();
        });

        $this->assertWasExecuted();

        $this->callThrottle();

        $this->assertWasThrottled();
    }

    /** @test */
    public function it_throttles_job_and_release_after_restore_rate()
    {
        $requestQuota = 10;

        $this->throttle->maximumQuota($requestQuota)->restoreRateInMin(2);

        $this->times($requestQuota, function ($i) use ($requestQuota) {
            $this->callThrottle();
            $this->assertNumberOfRequests($requestQuota-($i+1));
            $this->assertWasExecuted();

        });

        $this->callThrottle();
        $this->assertWasThrottled();
        $this->assertThrottleDurationEquals(30);

        //wait for restore
        $this->knownDate->addSeconds(20);
        $this->resetDeferrer();

        $this->callThrottle();
        $this->assertWasThrottled();
        $this->assertThrottleDurationEquals(10);

        //wait for restore
        $this->knownDate->addSeconds(40);
        $this->resetDeferrer();

        $this->callThrottle();
        $this->assertWasExecuted();
        $this->assertThrottleDurationEquals(0);
        $this->assertNumberOfRequests(0);

        //wait for restore
        // 2 request/min with max of 10 = 5 mins to restore
        // 5mins * 60sec = 300 sec
        $this->knownDate->addSeconds(300);
        $this->resetDeferrer();

        $this->callThrottle();
        $this->assertWasExecuted();
        $this->assertThrottleDurationEquals(null);
        $this->assertNumberOfRequests(9);
    }

    /** @test */
    public function it_restores_request_after_being_throttled()
    {
        $requestQuota = 10;

        $this->throttle->maximumQuota($requestQuota)->restoreRateInMin(2);

        // Us all request and start throttle
        $this->times($requestQuota, function ($i) use ($requestQuota) {
            $this->callThrottle();
            $this->assertNumberOfRequests($requestQuota-($i+1));
            $this->assertWasExecuted();

        });

        //wait for restore
        $this->knownDate->addSeconds(20);
        $this->callThrottle();
        $this->assertNumberOfRequests(0);

        //wait for restore
        $this->knownDate->addSeconds(60);
        $this->callThrottle();
        $this->assertNumberOfRequests(1);

        //wait for restore
        $this->knownDate->addSeconds(120);
        $this->callThrottle();
        $this->assertNumberOfRequests(4);
    }

    /** @test */
    public function it_throttles_restore_rates_less_than_a_second()
    {
        //  maximum request quota of 30 and a restore rate of one request every two seconds
        $requestQuota = 30;
        $restoreRate = (1/2);

        $this->throttle->maximumQuota($requestQuota)->restoreRate($restoreRate);

        $this->times($requestQuota, function ($i) use ($requestQuota) {
            $this->callThrottle();
            $this->assertNumberOfRequests($requestQuota-($i+1));
            $this->assertWasExecuted();
        });

        $this->callThrottle();
        $this->assertWasThrottled();
        $this->assertThrottleDurationEquals(2);

        //wait for restore
        $this->knownDate->addMilliseconds(200);
        $this->resetDeferrer();

        $this->callThrottle();
        $this->assertWasThrottled();
        $this->assertThrottleDurationEquals(1.8);

        //wait for restore
        $this->knownDate->addMilliseconds(4000);
        $this->resetDeferrer();

        $this->callThrottle();
        $this->assertWasExecuted();
        $this->assertThrottleDurationEquals(null);
        $this->assertNumberOfRequests(1);

        //wait for restore
        // 0.5 request/sec with max of 30 = 60 secs to restore
        // 60seconds * 1000(ms/sec) = 60000 sec
        $this->knownDate->addMilliseconds(60000);
        $this->resetDeferrer();

        $this->callThrottle();
        $this->assertWasExecuted();
        $this->assertThrottleDurationEquals(null);
        $this->assertNumberOfRequests($requestQuota-1);
    }


    //====================================
    //  Helpers
    //====================================

    public function assertWasExecuted()
    {
        $this->assertTrue($this->executedJob === true);
    }

    public function assertWasThrottled()
    {
        $this->assertTrue($this->throttleDuration > 0);
    }

    public function assertThrottleDurationEquals($duration)
    {
        $this->assertEquals($duration, $this->throttleDuration);
    }

    public function assertNumberOfRequests($numberOfRequests)
    {
        $this->assertEquals($numberOfRequests, $this->throttle->getRequestQuota());
    }

    public function times($number, callable $callback)
    {
        for($i=0; $i<$number; $i++) {
            $callback($i);
        }
    }

    public function callThrottle(): void
    {
        $this->throttle->then([$this,'success']);
    }

    public function success()
    {
        $this->executedJob = true;
        $this->throttleDuration = $this->deferrer->getCurrentTime() / 1000; //null;
    }

    public function resetDeferrer()
    {
        $this->deferrer->sleep(- $this->deferrer->getCurrentTime());
    }

}
