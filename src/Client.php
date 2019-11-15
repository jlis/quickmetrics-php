<?php

namespace Jlis\Quickmetrics;

use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use Jlis\Quickmetrics\Exception\RequestException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

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
     * Initializes the Quickmetrics client.
     *
     * @param Options $options
     * @param ClientInterface|null $httpClient
     * @param LoggerInterface $logger
     */
    public function __construct(Options $options, ClientInterface $httpClient = null, LoggerInterface $logger = null)
    {
        $this->options = $options;
        $this->httpClient = $httpClient ?: new \GuzzleHttp\Client([
            'headers'         => ['X-QM-KEY' => $options->getApiKey()],
            'timeout'         => $options->getTimeout(),
            'connect_timeout' => $options->getConnectTimeout(),
        ]);
        $this->logger = $logger;

        if ($options->isFlushableOnShutdown()) {
            register_shutdown_function(\Closure::fromCallable([$this, 'flush']));
        }
    }

    /**
     * Tracks a event with the current timestamp.
     *
     * @param string $name
     * @param float|int $value
     * @param string|null $dimension
     * @param int|null $timestamp
     *
     * @throws \Throwable
     * @throws RequestException
     */
    public function event($name, $value, $dimension = null, $timestamp = null)
    {
        $key = $name . $dimension;

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
            $this->flush(false);
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
     * @param bool $failSilent
     *
     * @throws \Throwable
     * @throws RequestException
     */
    public function flush($failSilent = true)
    {
        if (empty($this->events) || !$this->httpClient) {
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

            if (!$failSilent) {
                throw RequestException::fromGuzzleRequestException($exception);
            }
        } catch (\Throwable $exception) {
            if ($this->logger) {
                $this->logger->error('Cannot send metrics to Quickmetrics.', [
                    'exception' => $exception->getMessage(),
                    'code'      => $exception->getCode(),
                ]);
            }

            if (!$failSilent) {
                throw $exception;
            }
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
