<?php

namespace EolabsIo\AmazonSpApiThrottlingMiddleware\Contracts;

interface Deferrer
{
    public function getCurrentTime(): int;

    public function sleep(int $milliseconds);
}
