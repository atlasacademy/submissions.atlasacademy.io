<?php namespace Submission;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
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
     * @param string|null $token
     * @return string
     */
    public function create(string $eventUid, string $eventNodeUid, array $drops, $submitter = null, $token = null)
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
            "token" => $token,
            "created_at" => Carbon::now(),
            "updated_at" => Carbon::now()
        ];

        $this->connection->table("submissions")->insert($data);

        return $receipt;
    }

    public function getReceiptByToken(string $event_uid, string $event_node_uid, ?string $submitter, ?string $token): ?string
    {
        if ($token === null)
            return null;

        $submission = $this->connection->table("submissions")
            ->where("event_uid", "=", $event_uid)
            ->where("event_node_uid", "=", $event_node_uid)
            ->where("submitter", "=", $submitter)
            ->where("token", "=", $token)
            ->first();

        return $submission ? $submission->token : null;
    }

    /**
     * @param string $receipt
     * @return array|null
     */
    public function getSubmission(string $receipt)
    {
        $result = $this->connection->table("submissions")
            ->where("receipt", "=", $receipt)
            ->first();

        return $result ? (array)$result : null;
    }

    /**
     * @param string $event_uid
     * @param string $event_node_uid
     * @param int $limit
     * @param string|null $since_receipt
     * @return array
     */
    public function getSubmissions(string $event_uid, string $event_node_uid, int $limit, $since_receipt = null)
    {
        $query = $this->connection->table("submissions")
            ->where("event_uid", "=", $event_uid)
            ->where("event_node_uid", "=", $event_node_uid)
            ->orderBy("created_at", "DESC")
            ->take($limit);

        if ($since_receipt !== null) {
            $submission = $this->getSubmission($since_receipt);
            if (!$submission)
                return [];

            $query->where("created_at", "<", $submission["created_at"]);
        }

        $results = $query->get();

        return $this->castResultsToArray($results);
    }

    /**
     * @param string $receipt
     * @param bool $uploaded
     * @return bool
     */
    public function setUploaded(string $receipt, bool $uploaded): bool
    {
        return $this->connection->table("submissions")
            ->where("receipt", "=", $receipt)
            ->update([
                "uploaded" => boolval($uploaded),
                "updated_at" => Carbon::now()
            ]);
    }

    /**
     * @param string $receipt
     * @param bool $removed
     * @return bool
     */
    public function setRemoved(string $receipt, bool $removed): bool
    {
        return $this->connection->table("submissions")
            ->where("receipt", "=", $receipt)
            ->update([
                "removed" => boolval($removed),
                "updated_at" => Carbon::now()
            ]);
    }

    private function castResultsToArray(Collection $results): array
    {
        return $results
            ->map(function ($row) {
                return (array)$row;
            })
            ->toArray();
    }

}
