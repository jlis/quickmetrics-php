<?php

namespace Jlis\Quickmetrics\Tests;

use Jlis\Quickmetrics\Sampler;
use Jlis\Quickmetrics\Random\RandomGeneratorInterface;

class SamplerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|RandomGeneratorInterface
     */
    private $randomGenerator;
    /**
     * @var Sampler
     */
    private $sampler;

    public function setUp()
    {
        $this->randomGenerator = $this->createMock(RandomGeneratorInterface::class);
        $this->sampler = new Sampler($this->randomGenerator);
    }

    public function test_it_runs_the_closure_if_the_sample_rate_is_hte_one()
    {
        $result = false;
        $this->sampler->sample(1, static function () use (&$result) {
            $result = true;
        });

        $this->assertTrue($result);
    }

    public function test_it_runs_the_closure_if_the_the_sample_rate_is_hit()
    {
        $this->randomGenerator->expects(static::once())
            ->method('getRandomNumber')
            ->with(1, 100)
            ->willReturn(50);

        $result = false;
        $this->sampler->sample(0.6, static function () use (&$result) {
            $result = true;
        });

        $this->assertTrue($result);
    }

    public function test_it_doesnt_run_the_closure_if_the_the_sample_rate_is_missed()
    {
        $this->randomGenerator->expects(static::once())
            ->method('getRandomNumber')
            ->with(1, 100)
            ->willReturn(50);

        $result = false;
        $this->sampler->sample(0.4, static function () use (&$result) {
            $result = true;
        });

        $this->assertFalse($result);
    }
}
