<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Fiscal;

/**
 * @psalm-immutable
 */
final class ScaleRule
{
    private ?IndexedValue $limit;
    private ?IndexedValue $factor;

    public function __construct(?IndexedValue $limit, ?IndexedValue $factor)
    {
        $this->limit = $limit;
        $this->factor = $factor;
    }

    /**
     * @return float|int
     */
    public static function LIMIT(self $rule)
    {
        $limit = $rule->getLimit();
        if ($limit === null) {
            return PHP_FLOAT_MAX;
        }

        return $limit->getValue();
    }

    public static function FACTOR(self $rule): float
    {
        $factor = $rule->getFactor();
        if ($factor === null) {
            return 0;
        }

        return $factor->getValue();
    }

    /**
     * @param float $from
     * @param float $rhs
     */
    public static function make(?IndexedValue $limit = null, ?IndexedValue $factor = null): ScaleRule
    {
        return new self($limit, $factor);
    }

    public function getLimit(): ?IndexedValue
    {
        return $this->limit;
    }

    public function getFactor(): ?IndexedValue
    {
        return $this->factor;
    }
}
