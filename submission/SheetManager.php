<?php namespace Submission;

use Submission\Sheet\AdapterInterface;
use Submission\Sheet\SheetClient;
use Submission\Sheet\SubmissionV4Adapter;

class SheetManager implements AdapterInterface
{

    /**
     * @var DropRepository
     */
    private $dropRepository;
    /**
     * @var AdapterInterface
     */
    private $sheetAdapter = null;
    /**
     * @var SheetClient
     */
    private $sheetClient;

    public function __construct(DropRepository $dropRepository,
                                SheetClient $sheetClient)
    {
        $this->dropRepository = $dropRepository;
        $this->sheetClient = $sheetClient;
    }

    public function setSheetType(string $sheetType)
    {
        switch ($sheetType) {
            case "submissionsV4":
                $this->sheetAdapter = new SubmissionV4Adapter($this->dropRepository, $this->sheetClient);
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

    public function addSubmission(string $nodeUid, ?string $submitter, array $drops): ?int
    {
        return $this->sheetAdapter->addSubmission($nodeUid, $submitter, $drops);
    }

    public function revertSubmission(string $nodeUid, ?string $submitter, int $column, array $drops): ?int
    {
        return $this->sheetAdapter->revertSubmission($nodeUid, $submitter, $column, $drops);
    }
}
