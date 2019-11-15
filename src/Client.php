<?php

namespace Jlis\Quickmetrics;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;
use Jlis\Quickmetrics\Exception\RequestException;
use Psr\Log\LoggerInterface;

final class Client
{
    /**
     * @var Options
     */
    private $options;
    /**
     * @var ClientInterface|null
     */
    private $httpClient;
    /**
     * @var array
     */
    private $events = [];
    /**
     * @var LoggerInterface|null
     */
    private $logger;

    /**
     * @param string $apiKey
     */
    public function __construct(
        $apiKey,
        array $options = [],
        ClientInterface $httpClient = null,
        LoggerInterface $logger = null
    ) {
        $this->options = new Options($apiKey, $options);
        $this->httpClient = $httpClient ?: new \GuzzleHttp\Client([
            'headers'         => ['X-QM-KEY' => $this->options->getApiKey()],
            'timeout'         => $this->options->getTimeout(),
            'connect_timeout' => $this->options->getConnectTimeout(),
        ]);
        $this->logger = $logger;
    }

    /**
     * Tracks a event with the current timestamp.
     *
     * @param string      $name
     * @param float|int   $value
     * @param string|null $dimension
     * @param int|null    $timestamp
     *
     * @throws \Throwable
     * @throws RequestException
     */
    public function event($name, $value, $dimension = null, $timestamp = null)
    {
        $key = $name.$dimension;

        if (isset($this->events[$key])) {
            $this->events[$key]['values'][] = [$timestamp ?: time(), $value];
        } else {
            $this->events[$key] = [
                'name'      => $name,
                'dimension' => $dimension,
                'values'    => [
                    [$timestamp ?: time(), $value],
                ],
            ];
        }

        $maxBatchSize = $this->options->getMaxBatchSize();
        if ($maxBatchSize && $this->countEventValues() >= $maxBatchSize) {
            $this->flush();
        }
    }

    /**
     * Counts the actual values in the events.
     *
     * @return int
     */
    private function countEventValues()
    {
        $count = 0;
        foreach ($this->events as $event) {
            $count += count($event['values']);
        }

        return $count;
    }

    /**
     * Send the current batch of events to Quickmetrics.
     *
     * @throws \Throwable
     * @throws RequestException
     */
    public function flush()
    {
        if (! $this->countEventValues() || ! $this->httpClient) {
            return;
        }

        try {
            $this->httpClient->request('post', $this->options->getUrl(), [
                'json' => array_values($this->events),
            ]);

            $this->events = [];
        } catch (GuzzleRequestException $exception) {
            if ($this->logger) {
                $this->logger->error('Cannot send metrics to Quickmetrics.', [
                    'exception'   => $exception->getMessage(),
                    'code'        => $exception->getCode(),
                    'status_code' => $exception->hasResponse() ? $exception->getResponse()->getStatusCode() : null,
                ]);
            }

            throw RequestException::fromGuzzleRequestException($exception);
        } catch (\Throwable $exception) {
            if ($this->logger) {
                $this->logger->error('Cannot send metrics to Quickmetrics.', [
                    'exception' => $exception->getMessage(),
                    'code'      => $exception->getCode(),
                ]);
            }

            throw $exception;
        }
    }

    /**
     * @return array
     */
    public function getEvents()
    {
        return $this->events;
    }
}
