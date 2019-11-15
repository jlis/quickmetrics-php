<?php

namespace Jlis\Quickmetrics\Tests;

use Jlis\Quickmetrics\Options;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class OptionsTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_returns_the_default_options()
    {
        $options = new Options('api_key');

        $this->assertEquals('api_key', $options->getApiKey());
        $this->assertEquals('https://qckm.io/list', $options->getUrl());
        $this->assertEquals(100, $options->getMaxBatchSize());
        $this->assertEquals(1, $options->getTimeout());
        $this->assertEquals(1, $options->getConnectTimeout());
        $this->assertTrue($options->isFlushableOnShutdown());
    }

    /**
     * @dataProvider customValuesProvider
     *
     * @param string $name
     * @param mixed $value
     */
    public function test_it_sets_custom_values($name, $value)
    {
        $options = new Options('api_key', [$name => $value]);

        $this->assertEquals($options->getOptions()[$name], $value);
    }

    /**
     * @return array
     */
    public function customValuesProvider()
    {
        return [
            ['url', 'https://custom.url'],
            ['max_batch_size', 1337],
            ['timeout', 1337],
            ['connect_timeout', 1337],
            ['flush_on_shutdown', false],
            ['flush_on_shutdown', true],
        ];
    }

    /**
     * @dataProvider invalidOptionTypesProvider
     *
     * @param string $name
     * @param mixed $value
     * @param string $expectedType
     * @param string $actualType
     */
    public function test_it_validates_the_option_types($name, $value, $expectedType, $actualType)
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessageRegExp("/^The option \"$name\" with value (.*) is expected to be of type \"$expectedType\", but is of type \"$actualType\".$/");

        new Options('api_key', [$name => $value]);
    }

    /**
     * @return array
     */
    public function invalidOptionTypesProvider()
    {
        return [
            ['url', 123, 'string', 'integer'],
            ['max_batch_size', 'invalid', 'int', 'string'],
            ['timeout', 'invalid', 'int', 'string'],
            ['connect_timeout', 'invalid', 'int', 'string'],
            ['flush_on_shutdown', 'invalid', 'bool', 'string'],
            ['flush_on_shutdown', 1, 'bool', 'integer'],
        ];
    }

    /**
     * @dataProvider invalidUrlProvider
     *
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessageRegExp /^The option "url" with value "(.*)" is invalid.$/
     *
     * @param $url
     */
    public function test_it_validates_the_url_option($url)
    {
        new Options('api_key', ['url' => $url]);
    }

    /**
     * @return array
     */
    public function invalidUrlProvider()
    {
        return [
            ['invalid'],
            ['www.invalid.tld'],
        ];
    }
}
