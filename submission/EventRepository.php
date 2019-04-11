<?php namespace Submission;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class EventRepository
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
     * @param array $data
     * @return array|null
     */
    public function create(array $data)
    {
        $allowedKeys = ["uid", "sheet_type", "sheet_id", "name", "node_filter", "sort", "active"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["sort"] = 0;
        $filteredData["active"] = false;
        $filteredData["submittable"] = false;
        $filteredData["created_at"] = Carbon::now();
        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("events")->insert($filteredData);

        return $this->getEvent($data["uid"]);
    }

    /**
     * @param string $uid
     * @return array|null
     */
    public function getEvent(string $uid)
    {
        $result = $this->connection->table("events")
            ->where("uid", "=", $uid)
            ->first();

        return $result ? (array)$result : null;
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        $results = $this->connection->table("events")
            ->where("active", "=", true)
            ->orderBy("sort", "ASC")
            ->get();

        return $this->castResultsToArray($results);
    }

    /**
     * @param string $uid
     * @param bool $first
     */
    public function reorderEvents(string $uid, bool $first)
    {
        $uids = Collection::make($this->getEvents())
            ->filter(function ($event) use ($uid) {
                return $event["uid"] !== $uid;
            })
            ->pluck("uid")
            ->toArray();

        if ($first)
            array_unshift($uids, $uid);
        else
            array_push($uids, $uid);

        foreach ($uids as $k => $_uid) {
            $this->update($_uid, ["sort" => $k]);
        }
    }

    /**
     * @param string $uid
     * @param bool $active
     * @return array|null
     */
    public function setActive(string $uid, bool $active)
    {
        return $this->update($uid, ["active" => $active]);
    }

    /**
     * @param string $uid
     * @param bool $submittable
     * @return array|null
     */
    public function setSubmittable(string $uid, bool $submittable)
    {
        return $this->update($uid, ["submittable" => $submittable]);
    }

    /**
     * @param string $uid
     * @param array $data
     * @return array|null
     */
    public function update(string $uid, array $data)
    {
        $allowedKeys = ["name", "sort", "active", "submittable"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("events")
            ->where("uid", "=", $uid)
            ->update($filteredData);

        return $this->getEvent($uid);
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
