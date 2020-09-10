<?php

namespace App\Jobs;

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use Submission\ScreenshotRepository;

class ParseSubmissionJob extends Job
{

    /**
     * @var string
     */
    private $receipt;

    public function __construct(string $receipt)
    {
        $this->receipt = $receipt;
    }

    public function handle(ScreenshotRepository $screenshotRepository)
    {
        $screenshot = $screenshotRepository->getScreenshot($this->receipt);
        $source = $this->uploadScreenshot($screenshot);
        $this->sendToParser($screenshot, $source);
    }

    private function uploadScreenshot(array $screenshot): string
    {
        $endpoint = env('S3_ENDPOINT');
        $bucket = env('S3_BUCKET');
        $key = env('S3_KEY');
        $secret = env('S3_SECRET');
        $host = env('S3_HOST');
        $directory = env('SCREENSHOTS_DIRECTORY');

        $folder = date('Y-m');
        $filename = "{$screenshot['receipt']}.{$screenshot['extension']}";

        $s3 = new S3Client([
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $endpoint,
            'credentials' => [
                'key' => $key,
                'secret' => $secret,
            ],
        ]);

        $result = $s3->putObject([
            'Bucket' => $bucket,
            'Key' => "{$folder}/{$filename}",
            'SourceFile' => "{$directory}/{$filename}",
            'ACL' => 'public-read'
        ]);

        return "{$host}/{$folder}/{$filename}";
    }

    private function sendToParser(array $screenshot, string $source)
    {
        $host = env('PARSER_HOST');
        $key = env('PARSER_KEY');

        $data = [
            'key' => $key,
            'event' => $screenshot['event_uid'],
            'node' => $screenshot['event_node_uid'],
            'image' => $source,
            'type' => $screenshot['type'],
            'filename' => $screenshot['filename'],
            'submitter' => $screenshot['submitter'],
        ];

        $client = new Client([
            'base_uri' => $host,
        ]);

        $client->post('/submit', [
            'json' => $data
        ]);
    }

}
