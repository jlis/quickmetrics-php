<?php

namespace Jlis\Quickmetrics\Random;

class RandomGenerator implements RandomGeneratorInterface
{
    /**
     * {@inheritDoc}
     */
    public function getRandomNumber($min, $max)
    {
        return mt_rand($min, $max);
    }
}
