<?php namespace Submission;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EventNodeDropRepository
{

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->connection = $databaseManager->connection();
    }

    public function create(string $eventUid, string $eventNodeUid, array $data)
    {
        $allowedKeys = ["uid", "quantity", "rate", "apd", "count", "submissions", "sort"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["event_uid"] = $eventUid;
        $filteredData["event_node_uid"] = $eventNodeUid;
        $filteredData["created_at"] = Carbon::now();
        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("event_node_drops")->insert($filteredData);

        return $this->getDrop($eventUid, $eventNodeUid, $data["uid"]);
    }

    /**
     * @param string $eventUid
     * @param string $eventNodeUid
     * @param string $uid
     * @return bool
     */
    public function delete(string $eventUid, string $eventNodeUid, string $uid)
    {
        return (bool)$this->connection->table("event_node_drops")
            ->where("event_uid", "=", $eventUid)
            ->where("event_node_uid", "=", $eventNodeUid)
            ->where("uid", "=", $uid)
            ->delete();
    }

    /**
     * @param string $eventUid
     * @param string $eventNodeUid
     * @param string $uid
     * @return array|null
     */
    public function getDrop(string $eventUid, string $eventNodeUid, string $uid)
    {
        $drop = $this->connection->table("event_node_drops")
            ->where("event_uid", "=", $eventUid)
            ->where("event_node_uid", "=", $eventNodeUid)
            ->where("uid", "=", $uid)
            ->first();

        return $drop ? (array)$drop : null;
    }

    /**
     * @param string $eventUid
     * @param string $eventNodeUid
     * @return array
     */
    public function getDrops(string $eventUid, string $eventNodeUid)
    {
        $results = $this->connection->table("event_node_drops")
            ->where("event_uid", "=", $eventUid)
            ->where("event_node_uid", "=", $eventNodeUid)
            ->orderBy("sort", "ASC")
            ->get();

        return $this->castResultsToArray($results);
    }

    /**
     * @param string $eventUid
     * @param array $eventNodeUids
     * @return array
     */
    public function getDropsForNodes(string $eventUid, array $eventNodeUids)
    {
        if (!count($eventNodeUids))
            return [];

        $results = $this->connection->table("event_node_drops")
            ->where("event_uid", "=", $eventUid)
            ->whereIn("event_node_uid", $eventNodeUids)
            ->orderBy("event_node_uid", "ASC")
            ->orderBy("sort", "ASC")
            ->orderBy("uid", "ASC")
            ->get();

        return $this->castResultsToArray($results);
    }

    /**
     * @param string $eventUid
     * @param string $eventNodeUid
     * @param string $uid
     * @param array $data
     * @return array|null
     */
    public function update(string $eventUid, string $eventNodeUid, string $uid, array $data)
    {
        $allowedKeys = ["quantity", "rate", "apd", "count", "submissions", "sort"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("event_node_drops")
            ->where("event_uid", "=", $eventUid)
            ->where("event_node_uid", "=", $eventNodeUid)
            ->where("uid", "=", $uid)
            ->update($filteredData);

        return $this->getDrop($eventUid, $eventNodeUid, $uid);
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
