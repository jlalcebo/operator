<?php

namespace App\Prometheus;

use Closure;
use Exception;
use Illuminate\Support\Facades\Http;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class Pushgateway
{
    /**
     * Initialize the Pushgateway protocol.
     *
     * @param  string  $url
     * @return void
     */
    public function __construct(protected string $url)
    {
        //
    }

    /**
     * Push the data to the registry.
     *
     * @param  \Prometheus\CollectorRegistry  $registry
     * @param  string  $job
     * @param  array  $tags
     * @param  string  $method
     * @param  \Closure|null  $callback
     * @return void
     */
    public function push(
        CollectorRegistry $registry,
        string $job,
        array $tags = [],
        string $method = 'put',
        Closure $callback = null,
    ): void {
        $client = Http::contentType(RenderTextFormat::MIME_TYPE)
            ->timeout(30)
            ->withOptions([
                'connect_timeout' => 10,
            ]);

        if ($callback) {
            $client = $callback($client, $job, $tags);
        }

        try {
            $client->{$method}(
                $this->buildUrl($job, $tags),
                ! in_array($method, ['delete', 'get'])
                    ? (new RenderTextFormat)->render($registry->getMetricFamilySamples())
                    : [],
            );
        } catch (Exception $e) {
            //
        }
    }

    /**
     * Build the URL from job and tags.
     *
     * @param  string  $job
     * @param  array  $tags
     * @return string
     */
    protected function buildUrl(string $job, array $tags = []): string
    {
        $url = "{$this->url}/metrics/job/{$job}";

        foreach ($tags as $tag => $value) {
            $url .= "/{$tag}/{$value}";
        }

        return $url;
    }
}
