<?php

namespace Jlis\Quickmetrics;

use Jlis\Quickmetrics\Random\RandomGenerator;
use Jlis\Quickmetrics\Random\RandomGeneratorInterface;

final class Sampler
{
    /**
     * @var RandomGeneratorInterface
     */
    private $randomGenerator;

    public function __construct(RandomGeneratorInterface $randomGenerator = null)
    {
        $this->randomGenerator = $randomGenerator ?: new RandomGenerator();
    }

    /**
     * @param float $sampleRate
     */
    public function sample($sampleRate, \Closure $closure)
    {
        $random = $this->randomGenerator->getRandomNumber(1, 100) / 100.0;
        if ($sampleRate < 1 && $random > $sampleRate) {
            return;
        }

        $closure();
    }
}
