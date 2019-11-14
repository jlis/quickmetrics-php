<?php

namespace Jlis\Quickmetrics\Exception;

use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

class RequestException extends \RuntimeException
{
    /**
     * The response status code.
     *
     * @var int|null
     */
    private $statusCode;

    /**
     * @param GuzzleRequestException $exception
     *
     * @return RequestException
     */
    public static function fromGuzzleRequestException(GuzzleRequestException $exception)
    {
        $self = new self('Cannot send metrics to Quickmetrics.', $exception->getCode(), $exception);
        $self->setStatusCode($exception->hasResponse() ? $exception->getResponse()->getStatusCode() : null);

        return $self;
    }

    /**
     * @return int|null
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int|null $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
    }
}
