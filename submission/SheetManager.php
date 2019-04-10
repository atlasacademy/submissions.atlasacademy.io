<?php namespace Submission;

use Submission\Sheet\AdapterInterface;
use Submission\Sheet\SheetClient;
use Submission\Sheet\SubmissionV4Adapter;

class SheetManager implements AdapterInterface
{

    private $delay = 1;
    private $lastRequest = null;

    /**
     * @var AdapterInterface
     */
    private $sheetAdapter = null;
    /**
     * @var SheetClient
     */
    private $sheetClient;

    public function __construct(SheetClient $sheetClient)
    {
        $this->sheetClient = $sheetClient;
    }

    public function setSheetType(string $sheetType)
    {
        switch ($sheetType) {
            case "submissionsV4":
                $this->sheetAdapter = new SubmissionV4Adapter($this->sheetClient);
                return;
        }

        throw new \Exception("Invalid sheet type");
    }

    public function setSheetId(string $sheetId)
    {
        $this->throttleRequests();

        return $this->sheetAdapter->setSheetId($sheetId);
    }

    public function getNodes($regex)
    {
        $this->throttleRequests();

        return $this->sheetAdapter->getNodes($regex);
    }

    public function getNode(string $uid)
    {
        $this->throttleRequests();

        return $this->sheetAdapter->getNode($uid);
    }

    public function addSubmission(string $nodeUid, int $runs, $submitter, array $drops)
    {
        $this->throttleRequests();

        return $this->sheetAdapter->addSubmission($nodeUid, $runs, $submitter, $drops);
    }

    private function throttleRequests()
    {
        $now = microtime(true);

        if ($this->lastRequest && $this->lastRequest + $this->delay > $now) {
            $elapsed = $now - $this->lastRequest;
            $sleep = round(($this->delay - $elapsed) * 1000000);

            usleep($sleep);
        }

        $this->lastRequest = $now;
    }
}
