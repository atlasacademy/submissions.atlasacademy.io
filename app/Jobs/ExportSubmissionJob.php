<?php namespace App\Jobs;

use Submission\EventRepository;
use Submission\SheetManager;
use Submission\SubmissionRepository;

class ExportSubmissionJob extends Job
{

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

    public function __construct(string $submissionReceipt)
    {
        $this->submissionReceipt = $submissionReceipt;
    }

    public function handle(EventRepository $eventRepository,
                           SheetManager $sheetManager,
                           SubmissionRepository $submissionRepository)
    {
        $this->eventRepository = $eventRepository;
        $this->sheetManager = $sheetManager;
        $this->submissionRepository = $submissionRepository;

        // Check if submission has already been uploaded
        $submission = $this->submissionRepository->getSubmission($this->submissionReceipt);
        if (!$submission || $submission["uploaded"])
            throw new \Exception("Invalid submission.");

        // Check if submission event is submittable
        $event = $this->eventRepository->getEvent($submission["event_uid"]);
        if (!$event || !$event["active"] || !$event["submittable"])
            throw new \Exception("Event no longer allows submissions.");

        // Add submission
        $this->sheetManager->setSheetType($event["sheet_type"]);
        $this->sheetManager->setSheetId($event["sheet_id"]);
        $column = $this->sheetManager->addSubmission(
            $submission["event_node_uid"],
            $submission["submitter"],
            json_decode($submission["drops"], true)
        );

        if ($column !== null) {
            $this->submissionRepository->setColumn($this->submissionReceipt, $column);
            $this->submissionRepository->setUploaded($this->submissionReceipt, true);
        }
    }

}
