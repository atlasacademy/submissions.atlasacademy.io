<?php namespace Submission;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class DropRepository
{

    /**
     * @var Connection
     */
    private $connection;
    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(DatabaseManager $databaseManager,
                                Filesystem $filesystem)
    {
        $this->connection = $databaseManager->connection();
        $this->filesystem = $filesystem;
    }

    /**
     * @param array $data
     * @param bool $fetchImage
     * @return array|null
     */
    public function create(array $data, bool $fetchImage = false)
    {
        // Prevent strange uids from being generated. Possible path traversal via image download paths.
        if (!array_key_exists("uid", $data) && !preg_match('/^[A-Za-z0-9]+$/', $data["uid"]))
            throw new \Exception("Invalid UID for drop");

        $allowedKeys = ["uid", "name", "type", "quantity", "image", "image_original", "sort", "event", "active"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["created_at"] = Carbon::now();
        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("drops")->insert($filteredData);

        if ($fetchImage
            && array_key_exists("image_original", $data)
            && $data["image_original"]
        ) {
            $this->downloadImage($data["uid"]);
        }

        return $this->getDrop($data["uid"]);
    }

    /**
     * @param string $uid
     */
    public function downloadImage(string $uid)
    {
        $client = new Client();
        $drop = $this->getDrop($uid);
        $mimeTypes = ["image/jpeg", "image/png"];

        if (!$drop["image_original"])
            return $this->removeImage($uid);

        $response = $client->head($drop["image_original"]);

        // If status code, do nothing and try again at next attempt
        if ($response->getStatusCode() !== 200)
            return $drop;

        $type = array_search($response->getHeaderLine("Content-Type"), $mimeTypes);

        // If mime type is invalid. Remove image
        if ($type === false)
            return $this->removeImage($uid);

        $extension = $type === 0 ? "jpg" : "png";
        $client->request("GET", $drop["image_original"], [
            "sink" => base_path("/public/assets/drops/{$uid}.{$extension}")
        ]);

        $image = url("/assets/drops/{$uid}.{$extension}");

        $this->connection->table("drops")
            ->where("uid", "=", $uid)
            ->update([
                "image" => $image,
                "updated_at" => Carbon::now()
            ]);

        return $this->getDrop($uid);
    }

    /**
     * @param string $uid
     * @return array|null
     */
    public function getDrop(string $uid)
    {
        $result = $this->connection->table("drops")
            ->where("uid", "=", $uid)
            ->first();

        return $result ? (array)$result : null;
    }

    /**
     * @return array
     */
    public function getDrops()
    {
        $results = $this->connection->table("drops")
            ->where("active", "=", true)
            ->orderBy("sort", "ASC")
            ->get();

        return $this->castResultsToArray($results);
    }

    /**
     * @param string $uid
     * @return array|null
     */
    public function removeImage(string $uid)
    {
        $jpgPath = base_path("/public/assets/drops/{$uid}.png");
        $pngPath = base_path("/public/assets/drops/{$uid}.png");

        if ($this->filesystem->exists($pngPath))
            $this->filesystem->delete($pngPath);
        else if ($this->filesystem->exists($jpgPath))
            $this->filesystem->delete($jpgPath);

        $this->connection->table("drops")
            ->where("uid", "=", $uid)
            ->update([
                "image" => null,
                "updated_at" => Carbon::now()
            ]);

        return $this->getDrop($uid);
    }

    /**
     * @param string $uid
     * @param array $data
     * @param bool $fetchImage
     * @return array|null
     */
    public function update(string $uid, array $data, $fetchImage = false)
    {
        $oldDrop = $this->getDrop($uid);

        $allowedKeys = ["name", "type", "quantity", "image_original", "sort", "event", "active"];
        $filteredData = Arr::only($data, $allowedKeys);

        $filteredData["updated_at"] = Carbon::now();

        $this->connection->table("drops")
            ->where("uid", "=", $uid)
            ->update($filteredData);

        if ($fetchImage && array_key_exists("image_original", $data)) {
            if (!$data["image_original"]) {
                $this->removeImage($uid);
            } else if (!$oldDrop["image"] || $data["image_original"] !== $oldDrop["image_original"]) {
                $this->downloadImage($uid);
            }
        }

        return $this->getDrop($uid);
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
