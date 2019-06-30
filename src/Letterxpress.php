<?php

namespace Ben182\Letterxpress;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Carbon\Carbon;
use function GuzzleHttp\json_decode;
use Illuminate\Support\Collection;
use Exception;

class Letterxpress
{
    protected $liveUrl    = 'https://api.letterxpress.de/v1/';
    protected $sandboxUrl = 'https://sandbox.letterxpress.de/v1/';

    /**
     * The API Url to use.
     *
     * @var string
     */
    protected $baseUrl;

    /**
     * The Guzzle Client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    public function __construct()
    {
        $this->baseUrl = config('letterxpress.use_sandbox') ? $this->sandboxUrl : $this->liveUrl;

        $this->client = new Client([
            'base_uri' => $this->baseUrl,
            'timeout'  => 5.0,
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function setJob($pdfPath, Carbon $dispatchDate = null, $address = null, bool $printInColor = false, bool $doubleSidedPrinting = false, bool $internationalShipping = false, bool $c4MailingBag = false)
    {
        $base64file = base64_encode(file_get_contents($pdfPath));
        $checksum = md5($base64file);

        $payload = [
            'base64_file'     => $base64file,
            'base64_checksum' => $checksum,
            'specification' => [
                'color'   => $printInColor ? 4 : 1,
                'mode' => $doubleSidedPrinting ? 'duplex' : 'simplex',
                'ship' => $internationalShipping ? 'international' : 'national',
                'c4' => $c4MailingBag ? 'y' : 'n',
            ],
        ];

        if ($dispatchDate) {
            $payload['dispatchdate'] = $dispatchDate->format('d.m.Y');
        }

        $payload['address'] = $address ? $address : 'read';

        return $this->request('post', 'setJob', [
            'letter' => $payload,
        ]);
    }

    public function getPrice(int $pages, bool $printInColor = false, bool $doubleSidedPrinting = false, bool $internationalShipping = false, bool $c4MailingBag = false) {
        $payload = [
            'specification' => [
                'page' => $pages,
                'color'   => $printInColor ? 4 : 1,
                'mode' => $doubleSidedPrinting ? 'duplex' : 'simplex',
                'ship' => $internationalShipping ? 'international' : 'national',
                'c4' => $c4MailingBag ? 'y' : 'n',
            ],
        ];

        $response = $this->request('post', 'getPrice', [
            'letter' => $payload,
        ]);

        return floatval($response->letter->price);
    }

    public function getJob(int $jobId) {
        return $this->request('get', 'getJob/' . $jobId);
    }

    public function getOpenJobs() {

        $response = $this->request('get', 'getJobs/queue/0');

        return new Collection($response->jobs);
    }

    protected function request($method, $path, $options = []) {

        $options = array_merge($options, [
            'auth' => [
                'apikey' => config('letterxpress.api_key'),
                'username' => config('letterxpress.username'),
            ],
        ]);

        $response =  $this->client->request($method, $path, [
            RequestOptions::JSON => $options,
        ]);

        $response =  json_decode((string) $response->getBody());

        throw_if($response->status !== 200, new Exception('LetterXpress Request was not successful'));

        return $response;
    }
}
