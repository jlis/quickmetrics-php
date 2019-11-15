<?php

namespace Jlis\Quickmetrics\Tests;

use Psr\Log\LoggerInterface;
use GuzzleHttp\Psr7\Request;
use Jlis\Quickmetrics\Client;
use Jlis\Quickmetrics\Options;
use GuzzleHttp\ClientInterface;
use Jlis\Quickmetrics\Exception\RequestException;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

class ClientTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ClientInterface
     */
    private $httpClient;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private $logger;
    /**
     * @var Client
     */
    private $client;

    public function setUp()
    {
        $this->httpClient = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->client = new Client(
            new Options('api_key', ['url' => 'http://localhost', 'flush_on_shutdown' => false]),
            $this->httpClient,
            $this->logger
        );

    }

    public function test_it_collects_events()
    {
        $timestamp = time();
        $this->client->event('foo', 1.0, null, $timestamp);
        $this->client->event('foo', 2.0, null, $timestamp);
        $this->client->event('foo', 3.0, 'bar', $timestamp);

        $this->assertEquals([
            'foo'    => [
                'name'      => 'foo',
                'dimension' => null,
                'values'    => [
                    [$timestamp, 1.0],
                    [$timestamp, 2.0],
                ],
            ],
            'foobar' => [
                'name'      => 'foo',
                'dimension' => 'bar',
                'values'    => [
                    [$timestamp, 3.0],
                ],
            ],
        ], $this->client->getEvents());
    }

    public function test_it_doesnt_flush_when_max_batch_size_not_reached()
    {
        $options = new Options('api_key',
            [
                'url'               => 'http://localhost',
                'flush_on_shutdown' => false,
                'max_batch_size'    => 2,
            ]
        );

        $client = new Client($options, $this->httpClient, $this->logger);
        $timestamp = time();

        $this->httpClient->expects(static::never())
            ->method('request');

        $client->event('foo', 1.0, null, $timestamp);
    }

    public function test_it_flushes_when_max_batch_size_is_reached()
    {
        $options = new Options('api_key',
            [
                'url'               => 'http://localhost',
                'flush_on_shutdown' => false,
                'max_batch_size'    => 2,
            ]
        );

        $client = new Client($options, $this->httpClient, $this->logger);
        $timestamp = time();

        $this->httpClient->expects(static::once())
            ->method('request')
            ->with('post', 'http://localhost', [
                'json' => [
                    [
                        'name'      => 'foo',
                        'dimension' => null,
                        'values'    => [
                            [$timestamp, 1.0],
                            [$timestamp, 2.0],
                        ],
                    ],
                ],
            ]);

        $client->event('foo', 1.0, null, $timestamp);
        $client->event('foo', 2.0, null, $timestamp);

        $this->assertEmpty($client->getEvents());
    }

    public function test_it_throws_a_request_exception_when_the_request_fails()
    {
        $options = new Options('api_key',
            [
                'url'               => 'http://localhost',
                'flush_on_shutdown' => false,
                'max_batch_size'    => 1,
            ]
        );

        $client = new Client($options, $this->httpClient, $this->logger);

        $this->httpClient->expects(static::once())
            ->method('request')
            ->willReturnCallback(static function () {
                throw GuzzleRequestException::create(new Request('POST', 'http://localhost'));
            });

        $this->logger->expects(static::once())
            ->method('error')
            ->with('Cannot send metrics to Quickmetrics.');

        $this->expectException(RequestException::class);

        $client->event('foo', 1.0, null, time());
    }
}
