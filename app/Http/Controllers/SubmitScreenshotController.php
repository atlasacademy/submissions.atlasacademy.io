<?php

namespace App\Http\Controllers;

use App\Jobs\ParseScreenshotJob;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\Request;
use Laravel\Lumen\Http\ResponseFactory;
use Submission\EventNodeRepository;
use Submission\EventRepository;
use Submission\ScreenshotRepository;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SubmitScreenshotController extends Controller
{

    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var EventRepository
     */
    private $eventRepository;
    /**
     * @var EventNodeRepository
     */
    private $eventNodeRepository;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var ResponseFactory
     */
    private $responseFactory;
    /**
     * @var ScreenshotRepository
     */
    private $screenshotRepository;

    public function __construct(Dispatcher $dispatcher,
                                EventRepository $eventRepository,
                                EventNodeRepository $eventNodeRepository,
                                Request $request,
                                ResponseFactory $responseFactory,
                                ScreenshotRepository $screenshotRepository)
    {
        $this->dispatcher = $dispatcher;
        $this->eventRepository = $eventRepository;
        $this->eventNodeRepository = $eventNodeRepository;
        $this->request = $request;
        $this->responseFactory = $responseFactory;
        $this->screenshotRepository = $screenshotRepository;
    }

    public function post()
    {
        $event = $this->getEvent();
        $node = $this->getEventNode($event);
        $submitter = $this->getSubmitter();
        $screenshots = $this->makeScreenshots($event, $node, $submitter);

        $response = [
            "receipts" => []
        ];

        foreach ($screenshots as $screenshot) {
            $this->parseScreenshot($screenshot);
            $response["receipts"][] = [
                "filename" => $screenshot["filename"],
                "receipt" => $screenshot["receipt"]
            ];
        }

        $response["status"] = "Success";

        // Generate response
        return $this->responseFactory->json($response);
    }

    private function getEvent(): array
    {
        // Verify event is valid, submittable and parsable
        $event_uid = $this->request->get("event_uid", "");
        $event = $this->eventRepository->getEvent($event_uid);
        if (!$event || !$event["active"] || !$event["submittable"] || !$event["parsable"]) {
            throw new HttpException(422, "Invalid event uid.");
        }

        return $event;
    }

    private function getEventNode(array $event): array
    {
        // Verify node is valid
        $event_node_uid = $this->request->get("event_node_uid", "");
        $node = $this->eventNodeRepository->getNode($event["uid"], $event_node_uid);
        if (!$node || !$node["active"]) {
            throw new HttpException(422, "Invalid event node uid.");
        }

        return $node;
    }

    private function getSubmitter(): ?string
    {
        // Verify submitter name isn't too long
        $submitter = $this->request->get("submitter");
        if ($submitter !== null && strlen($submitter) > 50) {
            throw new HttpException(422, "Submitter name is too long. Maximum 50 characters.");
        }

        return $submitter;
    }

    private function makeScreenshots(array $event, array $node, ?string $submitter): array
    {
        $allowedExtensions = ["jpg", "jpeg", "png"];
        $allowedMimeTypes = ["image/jpeg", "image/png"];
        $oneMegabyte = pow(1024, 2);
        $maximumSize = 25 * $oneMegabyte;

        $files = $this->request->file("files");
        if (!$files || !is_array($files) || !count($files)) {
            throw new HttpException(422, "Missing files.");
        }

        // Validate each file before making screenshots
        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $extension = $this->extractExtension($filename);

            if (!in_array($extension, $allowedExtensions)
                || !in_array($file->getMimeType(), $allowedMimeTypes)
                || strlen($filename) > 255) {
                throw new HttpException(422, "Invalid file: {$filename}.");
            }

            if ($file->getSize() > $maximumSize) {
                throw new HttpException(422, "File too big: {$filename}.");
            }
        }

        // Make each screenshot
        $screenshots = [];
        foreach ($files as $file) {
            $filename = $file->getClientOriginalName();
            $extension = $this->extractExtension($filename);

            $receipt = $this->screenshotRepository->create(
                $event["uid"],
                $node["uid"],
                $filename,
                $extension,
                $submitter
            );

            $directory = env("SCREENSHOTS_DIRECTORY");
            $file->move($directory, "{$receipt}.{$extension}");

            $screenshots[] = $this->screenshotRepository->getScreenshot($receipt);
        }

        return $screenshots;
    }

    private function extractExtension(string $filename): ?string
    {
        $fileParts = explode(".", $filename);
        $extension = array_pop($fileParts);

        return $extension ?: null;
    }

    private function parseScreenshot(array $screenshot): void
    {
        $this->dispatcher->dispatch(new ParseScreenshotJob($screenshot["receipt"]));
    }

}
