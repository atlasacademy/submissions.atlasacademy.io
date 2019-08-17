<?php namespace Submission;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EventNodeRepository
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
     * @param array $data
     * @return array|null
     */
    public function create(string $eventUid, array $data)
    {
        $allowedKeys = ["uid", "name", "sheet_name", "cost", "base_qp", "submissions", "submitters", "sort", "active"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["event_uid"] = $eventUid;
        $filteredData["created_at"] = Carbon::now();
        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("event_nodes")->insert($filteredData);

        return $this->getNode($eventUid, $data["uid"]);
    }

    /**
     * @param string $eventUid
     * @param string $uid
     * @return array|null
     */
    public function getNode(string $eventUid, string $uid)
    {
        $node = $this->connection->table("event_nodes")
            ->where("event_uid", "=", $eventUid)
            ->where("uid", "=", $uid)
            ->first();

        return $node ? (array)$node : null;
    }

    /**
     * @param string $eventUid
     * @return array
     */
    public function getNodes(string $eventUid): array
    {
        $results = $this->connection->table("event_nodes")
            ->where("event_uid", "=", $eventUid)
            ->where("active", "=", true)
            ->orderBy("sort", "ASC")
            ->get();

        return $this->castResultsToArray($results);
    }

    /**
     * @param string $eventUid
     * @param string $uid
     * @param bool $active
     * @return array|null
     */
    public function setActive(string $eventUid, string $uid, bool $active)
    {
        $data = ["active" => $active];

        return $this->update($eventUid, $uid, $data);
    }

    /**
     * @param string $eventUid
     * @param string $uid
     * @param array $data
     * @return array|null
     */
    public function update(string $eventUid, string $uid, array $data)
    {
        $allowedKeys = ["name", "sheet_name", "cost", "base_qp", "submissions", "submitters", "sort", "active"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("event_nodes")
            ->where("event_uid", "=", $eventUid)
            ->where("uid", "=", $uid)
            ->update($filteredData);

        return $this->getNode($eventUid, $uid);
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
