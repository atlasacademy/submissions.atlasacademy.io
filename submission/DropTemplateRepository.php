<?php

namespace Submission;

use Carbon\Carbon;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DropTemplateRepository
{

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(DatabaseManager $databaseManager)
    {
        $this->connection = $databaseManager->connection();
    }

    public function getDropTemplates(string $dropUid, ?int $quantity): array
    {
        $results = $this->connection
            ->table("drop_templates")
            ->where("drop_uid", "=", $dropUid)
            ->where("quantity", "=", $quantity)
            ->orderBy("bonus", "ASC")
            ->get([
                // Do not return the image
                "id",
                "drop_uid",
                "quantity",
                "bonus",
                "created_at",
                "updated_at",
            ]);

        return $this->castResultsToArray($results);
    }

    /**
     * @param string $dropUid
     * @param int|null $quantity
     * @param int|null $bonus
     * @param string $image
     * @return bool
     */
    public function update(string $dropUid, ?int $quantity, ?int $bonus, string $image): bool
    {
        $count = $this->connection
            ->table("drop_templates")
            ->where("drop_uid", "=", $dropUid)
            ->where("quantity", "=", $quantity)
            ->where("bonus", "=", $bonus)
            ->count();

        if ($count) {
            return boolval($this->connection
                ->table("drop_templates")
                ->where("drop_uid", "=", $dropUid)
                ->where("quantity", "=", $quantity)
                ->where("bonus", "=", $bonus)
                ->update([
                    "image" => $image,
                    "updated_at" => Carbon::now(),
                ]));
        } else {
            return boolval($this->connection
                ->table("drop_templates")
                ->insert([
                    "drop_uid" => $dropUid,
                    "quantity" => $quantity,
                    "bonus" => $bonus,
                    "image" => $image,
                    "created_at" => Carbon::now(),
                    "updated_at" => Carbon::now(),
                ]));
        }
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
