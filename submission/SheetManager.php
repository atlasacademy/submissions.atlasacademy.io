<?php namespace Submission;

use Submission\Sheet\AdapterInterface;
use Submission\Sheet\SheetClient;
use Submission\Sheet\SubmissionV4Adapter;

class SheetManager implements AdapterInterface
{

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
        return $this->sheetAdapter->setSheetId($sheetId);
    }

    public function getNodes($regex)
    {
        return $this->sheetAdapter->getNodes($regex);
    }

    public function getNode(string $uid)
    {
        return $this->sheetAdapter->getNode($uid);
    }

    public function addSubmission(string $nodeUid, $submitter, array $drops)
    {
        return $this->sheetAdapter->addSubmission($nodeUid, $submitter, $drops);
    }
}
