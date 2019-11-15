<?php

namespace Jlis\Quickmetrics\Random;

class RandomGenerator implements RandomGeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getRandomNumber($min, $max)
    {
        return mt_rand($min, $max);
    }
}
