<?php

namespace EolabsIo\AmazonSpApiThrottlingMiddleware\Tests;

use EolabsIo\AmazonSpApiThrottlingMiddleware\Contracts\Deferrer;

class TestDeferrer implements Deferrer
{
    /** @var int */
    protected $currentTime = 0;

    public function getCurrentTime(): int
    {
        return $this->currentTime;
    }

    public function sleep(int $milliseconds)
    {
        $this->currentTime += $milliseconds;
    }
}
