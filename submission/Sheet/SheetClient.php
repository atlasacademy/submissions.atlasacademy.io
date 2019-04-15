<?php namespace Submission\Sheet;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Arr;

class SheetClient
{
    private $delay = 1;
    private $lastRequest = null;

    /**
     * @var Google_Client
     */
    private $googleClient = null;
    /**
     * @var Google_Service_Sheets
     */
    private $sheetService = null;

    /**
     * @param string $sheetId
     * @param string $range
     * @return array
     * @throws \Exception
     */
    public function getCells(string $sheetId, string $range)
    {
        $this->throttleRequests();

        $response = $this->service()->spreadsheets_values->get($sheetId, $range);
        $results = $response->getValues();

        return $results;
    }

    /**
     * @param string $sheetId
     * @param string $range
     * @return array
     * @throws \Exception
     */
    public function getCellsRaw(string $sheetId, string $range)
    {
        $this->throttleRequests();

        $response = $this->service()->spreadsheets_values->get($sheetId, $range, [
            "valueRenderOption" => "UNFORMATTED_VALUE",
            "dateTimeRenderOption" => "SERIAL_NUMBER"
        ]);
        $results = $response->getValues();

        return $results;
    }

    public function updateCells(string $sheetId, string $range, array $values)
    {
        $this->throttleRequests();

        $values = $this->formatNullValues($values);

        $requestBody = new Google_Service_Sheets_ValueRange();
        $requestBody->setMajorDimension("ROWS");
        $requestBody->setRange($range);
        $requestBody->setValues($values);
        $options = [
            "valueInputOption" => "RAW"
        ];
        $this->service()->spreadsheets_values->update($sheetId, $range, $requestBody, $options);

        return true;
    }

    /**
     * @return Google_Client
     * @throws \Exception
     */
    private function client()
    {
        if ($this->googleClient)
            return $this->googleClient;

        $this->googleClient = $this->makeClient();

        return $this->googleClient;
    }

    /**
     * @return \Google_Client
     * @throws \Exception
     */
    private function makeClient()
    {
        $clientId = env("GOOGLE_CLIENT_ID");
        $clientSecret = env("GOOGLE_CLIENT_SECRET");
        $clientToken = json_decode(env("GOOGLE_CLIENT_TOKEN"), true);

        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri("urn:ietf:wg:oauth:2.0:oob");
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        if ($clientToken) {
            $client->setAccessToken($clientToken);
        }

        return $client;
    }

    /**
     * @return Google_Service_Sheets
     * @throws \Exception
     */
    private function service()
    {
        if ($this->sheetService)
            return $this->sheetService;

        $this->sheetService = new Google_Service_Sheets($this->client());

        return $this->sheetService;
    }

    private function throttleRequests()
    {
        $now = microtime(true);

        if ($this->lastRequest && $this->lastRequest + $this->delay > $now) {
            $elapsed = $now - $this->lastRequest;
            $sleep = round(($this->delay - $elapsed) * 1000000);

            usleep($sleep);
        }

        $this->lastRequest = $now;
    }

    private function formatNullValues(array $values)
    {
        return array_map(function ($value) {
            if ($value === null)
                return Google_Service_Sheets_ValueRange::NULL_VALUE;

            if (is_array($value))
                return $this->formatNullValues($value);

            return $value;
        }, $values);
    }

}
