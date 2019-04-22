<?php namespace App\Console\Commands;

use Google_Client;
use Google_Service_Sheets;

class GenerateTokenCommand extends Command
{

    protected $name = "submissions:generate_token";
    protected $description = "Generates a Google OAUTH token using client id and secret.";

    public function handle()
    {
        $clientId = env("GOOGLE_CLIENT_ID");
        $clientSecret = env("GOOGLE_CLIENT_SECRET");

        $client = new Google_Client();
        $client->setApplicationName('Google Sheets API PHP Quickstart');
        $client->setScopes(Google_Service_Sheets::SPREADSHEETS);
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri("urn:ietf:wg:oauth:2.0:oob");
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        $authUrl = $client->createAuthUrl();
        $this->output->text("Open the following link in your browser: {$authUrl}");
        $authCode = $this->output->ask("Enter verification code:");
        $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);

        $this->output->text("Insert the following in GOOGLE_CLIENT_TOKEN:");
        $this->output->text(json_encode($accessToken));
    }

}