<?php

namespace App\Jobs;

class ParseScreenshotJob extends Job
{

    /**
     * @var string
     */
    private $receipt;

    public function __construct(string $receipt)
    {
        $this->receipt = $receipt;
    }

    public function handle()
    {

    }

}
