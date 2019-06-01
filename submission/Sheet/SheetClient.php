<?php namespace Submission\Sheet;

use Google_Client;
use Google_Service_Sheets;
use Google_Service_Sheets_ValueRange;
use Illuminate\Support\Arr;

class SheetClient
{
    private $delay = 2;
    private $delayStep = 0;
    private $delayableErrorCodes = [429, 503];

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

        try {
            $response = $this->service()->spreadsheets_values->get($sheetId, $range);
        } catch (\Google_Service_Exception $e) {
            if (!in_array($e->getCode(), $this->delayableErrorCodes)) {
                throw new \Exception("SheetClient->getCells({$sheetId}, {$range})", 0, $e);
            }

            $this->delayStep++;

            return $this->getCells($sheetId, $range);
        }

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

        try {
            $response = $this->service()->spreadsheets_values->get($sheetId, $range, [
                "valueRenderOption" => "UNFORMATTED_VALUE",
                "dateTimeRenderOption" => "SERIAL_NUMBER"
            ]);
        } catch (\Google_Service_Exception $e) {
            if (!in_array($e->getCode(), $this->delayableErrorCodes)) {
                throw new \Exception("SheetClient->getCellsRaw({$sheetId}, {$range})", 0, $e);
            }

            $this->delayStep++;

            return $this->getCellsRaw($sheetId, $range);
        }

        $results = $response->getValues();

        return $results;
    }

    public function updateCells(string $sheetId, string $range, array $values)
    {
        $this->throttleRequests();

        $formattedValues = $this->formatNullValues($values);

        $requestBody = new Google_Service_Sheets_ValueRange();
        $requestBody->setMajorDimension("ROWS");
        $requestBody->setRange($range);
        $requestBody->setValues($formattedValues);
        $options = [
            "valueInputOption" => "RAW"
        ];

        try {
            $this->service()->spreadsheets_values->update($sheetId, $range, $requestBody, $options);
        } catch (\Google_Service_Exception $e) {
            if (!in_array($e->getCode(), $this->delayableErrorCodes)) {
                $message = "SheetClient->updateCells({$sheetId}, {$range}, " . json_encode($formattedValues) . ")";
                throw new \Exception($message, 0, $e);
            }

            $this->delayStep++;

            return $this->updateCells($sheetId, $range, $values);
        }

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

        if (!$clientId || !$clientSecret || !$clientToken)
            throw new \Exception("Google Sheet Client failed to initialize. Missing required parameters.");

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
        if ($this->delayStep > 6) {
            throw new \Exception("Throttle step has exceeded limit");
        }

        sleep(pow($this->delay, $this->delayStep));
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
