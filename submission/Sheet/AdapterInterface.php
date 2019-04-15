<?php namespace Submission\Sheet;

interface AdapterInterface
{

    public function setSheetId(string $sheetId);
    public function getNodes($regex);
    public function getNode(string $uid);
    public function addSubmission(string $nodeUid, $submitter, array $drops);

}
