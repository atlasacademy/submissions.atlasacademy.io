<?php

namespace App\Jobs;

use Illuminate\Contracts\Bus\Dispatcher;
use Submission\EventRepository;
use Submission\SheetManager;
use Submission\SubmissionRepository;

class RevertSubmissionJob extends Job
{

    const MAXIMUM_ATTEMPTS = 5;

    /**
     * @var int
     */
    private $attempts;
    /**
     * @var Dispatcher
     */
    private $dispatcher;
    /**
     * @var EventRepository
     */
    private $eventRepository;
    /**
     * @var SheetManager
     */
    private $sheetManager;
    /**
     * @var string
     */
    private $submissionReceipt;
    /**
     * @var SubmissionRepository
     */
    private $submissionRepository;

    public function __construct(string $submissionReceipt, int $attempts = 0)
    {
        $this->submissionReceipt = $submissionReceipt;
        $this->attempts = $attempts;
    }

    public function handle(Dispatcher $dispatcher,
                           EventRepository $eventRepository,
                           SheetManager $sheetManager,
                           SubmissionRepository $submissionRepository)
    {
        $this->dispatcher = $dispatcher;
        $this->eventRepository = $eventRepository;
        $this->sheetManager = $sheetManager;
        $this->submissionRepository = $submissionRepository;

        // Check if submission is valid
        $submission = $this->submissionRepository->getSubmission($this->submissionReceipt);
        if (!$submission || $submission['removed'] || $this->attempts > static::MAXIMUM_ATTEMPTS)
            throw new \Exception("Invalid submission.");

        // Now check if submission has been uploaded yet. If not, wait several attempts
        if (!$submission["uploaded"]) {
            $this->dispatcher->dispatch(new static($this->submissionReceipt, $this->attempts+1));
            return;
        }

        // Check if submission event is valid
        $event = $this->eventRepository->getEvent($submission["event_uid"]);
        if (!$event)
            throw new \Exception("Invalid event.");

        // Revert submission
        $this->sheetManager->setSheetType($event["sheet_type"]);
        $this->sheetManager->setSheetId($event["sheet_id"]);
        $this->sheetManager->revertSubmission(
            $submission["event_node_uid"],
            $submission["submitter"],
            json_decode($submission["drops"], true)
        );

        // Set submission as removed
        $this->submissionRepository->setRemoved($this->submissionReceipt, true);
    }

}
