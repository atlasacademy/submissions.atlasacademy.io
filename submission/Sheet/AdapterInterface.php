<?php namespace Submission\Sheet;

interface AdapterInterface
{

    public function setSheetId(string $sheetId);
    public function getNodes($regex);
    public function getNode(string $uid);
    public function addSubmission(string $nodeUid, ?string $submitter, array $drops): ?int;
    public function revertSubmission(string $nodeUid, ?string $submitter, int $column, array $drops): ?int;

}
