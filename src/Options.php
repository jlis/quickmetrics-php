<?php

namespace Jlis\Quickmetrics;

use Symfony\Component\OptionsResolver\OptionsResolver;

final class Options
{
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var array<string, mixed>
     */
    private $options;

    /**
     * @param string $apiKey
     * @param array $options
     */
    public function __construct($apiKey, array $options = [])
    {
        $this->apiKey = $apiKey;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    private function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'url'               => 'https://qckm.io/list',
            'max_batch_size'    => 100,
            'timeout'           => 1,
            'connect_timeout'   => 1,
            'flush_on_shutdown' => true,
        ]);

        $resolver->setAllowedTypes('url', 'string');
        $resolver->setAllowedTypes('max_batch_size', 'int');
        $resolver->setAllowedTypes('timeout', 'int');
        $resolver->setAllowedTypes('connect_timeout', 'int');
        $resolver->setAllowedTypes('flush_on_shutdown', 'bool');

        $resolver->setAllowedValues('url', function ($value) {
            return $this->validateUrl($value);
        });
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function validateUrl($url)
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->options['url'];
    }

    /**
     * @return int
     */
    public function getMaxBatchSize()
    {
        return $this->options['max_batch_size'];
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->options['timeout'];
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->options['connect_timeout'];
    }

    /**
     * @return bool
     */
    public function isFlushableOnShutdown()
    {
        return $this->options['flush_on_shutdown'];
    }
}
