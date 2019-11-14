<?php

namespace Jlis\Quickmetrics\Random;

interface RandomGeneratorInterface
{
    /**
     * Returns a random number in the given range.
     *
     * @param int $min
     * @param int $max
     *
     * @return int
     */
    public function getRandomNumber($min, $max);
}
