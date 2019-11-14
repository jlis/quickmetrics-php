<?php

namespace Jlis\Quickmetrics;

use Psr\Log\LoggerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Jlis\Quickmetrics\Exception\RequestException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

class Client
{
    /**
     * @var Options
     */
    private static $options;
    /**
     * @var ClientInterface|null
     */
    private static $client;
    /**
     * @var array
     */
    private static $events = [];
    /**
     * @var LoggerInterface|null
     */
    private static $logger;

    /**
     * Initializes the Quickmetrics client.
     *
     * @param Options $options
     * @param ClientInterface|null $client
     * @param LoggerInterface $logger
     */
    public static function init(Options $options, ClientInterface $client = null, LoggerInterface $logger = null)
    {
        self::$options = $options;
        self::$client = $client ?: new \GuzzleHttp\Client([
            'headers'         => ['X-QM-KEY' => $options->getApiKey()],
            'timeout'         => $options->getTimeout(),
            'connect_timeout' => $options->getConnectTimeout(),
        ]);
        self::$logger = $logger;

        if ($options->isFlushableOnShutdown()) {
            register_shutdown_function(static function () {
                self::flush(true);
            });
        }
    }

    /**
     * Send the current batch of events to Quickmetrics.
     *
     * @param bool $failSilent
     *
     * @throws RequestException
     * @throws GuzzleException
     */
    public static function flush($failSilent = false)
    {
        if (empty(self::$events) || !self::$client) {
            return;
        }

        try {
            self::$client->request('post', self::$options->getUrl(), [
                'json' => array_values(self::$events),
            ]);
        } catch (GuzzleRequestException $exception) {
            if (self::$logger) {
                self::$logger->error('Cannot send metrics to Quickmetrics.', [
                    'exception'   => $exception->getMessage(),
                    'code'        => $exception->getCode(),
                    'status_code' => $exception->hasResponse() ? $exception->getResponse()->getStatusCode() : null,
                ]);
            }

            if (!$failSilent) {
                throw RequestException::fromGuzzleRequestException($exception);
            }
        } catch (GuzzleException $exception) {
            if (self::$logger) {
                self::$logger->error('Cannot send metrics to Quickmetrics.', [
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
     * Tracks a event with the current timestamp.
     *
     * @param string $name
     * @param float|int $value
     * @param string|null $dimension
     * @param int|null $timestamp
     *
     * @throws GuzzleException
     */
    public static function event($name, $value, $dimension = null, $timestamp = null)
    {
        $key = $name . $dimension;

        if (isset(self::$events[$key])) {
            self::$events[$key]['values'][] = [$timestamp ?: time(), $value];

            return;
        }

        self::$events[$key] = [
            'name'      => $name,
            'dimension' => $dimension,
            'values'    => [
                [$timestamp ?: time(), $value],
            ],
        ];

        $maxBatchSize = self::$options->getMaxBatchSize();
        if ($maxBatchSize && count(self::$events) >= $maxBatchSize) {
            self::flush();
        }
    }
}
