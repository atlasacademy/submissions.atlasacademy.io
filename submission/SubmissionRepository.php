<?php namespace Submission;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Ramsey\Uuid\Uuid;

class SubmissionRepository
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
     * @param array $drops
     * @param string|null $submitter
     * @return string
     */
    public function create(string $eventUid, string $eventNodeUid, array $drops, $submitter = null)
    {
        $receipt = Uuid::uuid1()->toString();

        $data = [
            "receipt" => $receipt,
            "event_uid" => $eventUid,
            "event_node_uid" => $eventNodeUid,
            "submitter" => $submitter,
            "drops" => json_encode($drops),
            "uploaded" => false,
            "removed" => false,
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ];

        $this->connection->table("submissions")->insert($data);

        return $receipt;
    }

}
