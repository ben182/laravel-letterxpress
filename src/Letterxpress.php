<?php

namespace Ben182\Letterxpress;

use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Illuminate\Support\Collection;
use function GuzzleHttp\json_decode;
use Ben182\Letterxpress\Exceptions\FilesizeIsTooLarge;
use Ben182\Letterxpress\Exceptions\RequestNotSuccessfulException;

class Letterxpress
{
    protected $liveUrl = 'https://api.letterxpress.de/v1/';
    protected $sandboxUrl = 'https://sandbox.letterxpress.de/v1/';
    const MAX_PDF_FILESIZE = 15; // MB

    /**
     * The Guzzle Client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    public function __construct()
    {
        $baseUrl = config('letterxpress.use_sandbox') ? $this->sandboxUrl : $this->liveUrl;

        $this->client = new Client([
            'base_uri' => $baseUrl,
            'timeout'  => 5.0,
            'headers'  => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    public function createJob($pdfPath, Carbon $dispatchDate = null, string $address = null, bool $printInColor = false, bool $doubleSidedPrinting = false, bool $internationalShipping = false, bool $c4MailingBag = false)
    {
        throw_unless($this->isPdfSizeAllowed($pdfPath), new FilesizeIsTooLarge($pdfPath));

        $base64file = base64_encode(file_get_contents($pdfPath));
        $checksum = md5($base64file);

        $payload = [
            'base64_file'     => $base64file,
            'base64_checksum' => $checksum,
            'specification'   => [
                'color'   => $printInColor ? 4 : 1,
                'mode'    => $doubleSidedPrinting ? 'duplex' : 'simplex',
                'ship'    => $internationalShipping ? 'international' : 'national',
                'c4'      => $c4MailingBag ? 'y' : 'n',
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

    protected function isPdfSizeAllowed($file)
    {
        return round(filesize($file) / 1024 / 1024) <= static::MAX_PDF_FILESIZE;
    }

    public function getPrice(int $pages, bool $printInColor = false, bool $doubleSidedPrinting = false, bool $internationalShipping = false, bool $c4MailingBag = false)
    {
        $payload = [
            'specification' => [
                'page'    => $pages,
                'color'   => $printInColor ? 4 : 1,
                'mode'    => $doubleSidedPrinting ? 'duplex' : 'simplex',
                'ship'    => $internationalShipping ? 'international' : 'national',
                'c4'      => $c4MailingBag ? 'y' : 'n',
            ],
        ];

        $response = $this->request('post', 'getPrice', [
            'letter' => $payload,
        ]);

        return floatval($response->letter->price);
    }

    public function getJob(int $jobId)
    {
        $response = $this->request('get', 'getJob/'.$jobId);

        return $this->castJob($response->job);
    }

    public function listInvoices()
    {
        return $this->request('get', 'listInvoices');
    }

    public function getBalance()
    {
        $response = $this->request('get', 'getBalance');
        $balance = $response->balance;
        $balance->value = floatval($balance->value);

        return $balance;
    }

    public function deleteJob(int $jobId)
    {
        return $this->request('delete', 'deleteJob/'.$jobId);
    }

    public function getQueuedJobs(int $sinceDays = 0)
    {
        $response = $this->request('get', 'getJobs/queue/'.$sinceDays);

        return (new Collection($response->jobs))->map(function ($job) {
            return $this->castJob($job);
        });
    }

    public function getTimedJobs()
    {
        $response = $this->request('get', 'getJobs/timer');

        return (new Collection($response->jobs))->map(function ($job) {
            return $this->castJob($job);
        });
    }

    public function getSentJobs(int $sinceDays = 0)
    {
        $response = $this->request('get', 'getJobs/sent/'.$sinceDays);

        return (new Collection($response->jobs))->map(function ($job) {
            return $this->castJob($job);
        });
    }

    public function getOpenJobs()
    {
        return $this->getQueuedJobs()->merge($this->getTimedJobs());
    }

    protected function castJob($job)
    {
        $job->date = is_null($job->date) ? null : new Carbon($job->date);
        $job->dispatchdate = is_null($job->dispatchdate) ? null : new Carbon($job->dispatchdate);
        $job->sentdate = is_null($job->sentdate) ? null : new Carbon($job->sentdate);

        $job->jid = intval($job->jid);
        $job->pages = intval($job->pages);
        $job->color = intval($job->color);

        if (isset($job->cost)) {
            $job->cost = floatval($job->cost);
            $job->cost_vat = floatval($job->cost_vat);
        }

        if (isset($job->price)) {
            $job->price = floatval($job->price);
        }

        return $job;
    }

    protected function request($method, $path, $options = [])
    {
        $options = array_merge($options, [
            'auth' => [
                'apikey'   => config('letterxpress.api_key'),
                'username' => config('letterxpress.username'),
            ],
        ]);

        $response = $this->client->request($method, $path, [
            RequestOptions::JSON => $options,
        ]);

        $responseBody = json_decode((string) $response->getBody());

        throw_if($response->getStatusCode() !== 200 || $responseBody->status !== 200, new RequestNotSuccessfulException((string) $response->getBody(), $response->getStatusCode()));

        return $responseBody;
    }
}
