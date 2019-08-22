<?php

namespace Submission;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\Uuid;

class ScreenshotRepository
{

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->connection = $databaseManager->connection();
    }

    /**
     * @param string $eventUid
     * @param string $eventNodeUid
     * @param string $filename
     * @param string $extension
     * @param string|null $submitter
     * @return string
     */
    public function create(string $eventUid, string $eventNodeUid, string $filename, string $extension, ?string $submitter = null)
    {
        $receipt = Uuid::uuid1()->toString();

        $data = [
            "receipt" => $receipt,
            "event_uid" => $eventUid,
            "event_node_uid" => $eventNodeUid,
            "submitter" => $submitter,
            "filename" => $filename,
            "extension" => $extension,
            "parse_result" => null,
            "parsed" => false,
            "submitted" => false,
            "removed" => false,
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now(),
            "parsed_at" => null,
        ];

        $this->connection->table("screenshots")->insert($data);

        return $receipt;
    }

    /**
     * @param string $receipt
     * @return array|null
     */
    public function getScreenshot(string $receipt)
    {
        $result = $this->connection->table("screenshots")
            ->where("receipt", "=", $receipt)
            ->first();

        return $result ? (array)$result : null;
    }

}
