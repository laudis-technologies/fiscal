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

use DateTime;

final class Range
{
    private int $start;
    private int $end;

    private const SECONDS_IN_DAY = 60 * 60 * 24;

    public function __construct(int $start, int $end)
    {
        $this->start = $start;
        $this->end = $end;
    }

    public static function fromStringFormat(string $start, string $end): Range
    {
        $startTimestamp = DateTime::createFromFormat('Y-m-d', $start)->getTimestamp();
        $endTimestamp = DateTime::createFromFormat('Y-m-d', $end)->getTimestamp();

        return new self(
            $startTimestamp - ($startTimestamp % self::SECONDS_IN_DAY),
            $endTimestamp - ($endTimestamp % self::SECONDS_IN_DAY)
        );
    }

    /**
     * This range will become the range on the lowest end,
     * The other range will become the range in the middle or highest end,
     * The returned range is the range at the highest end.
     */
    public function sortAndSplit(Range $other): ?Range
    {
        if ($this->start > $other->start) {
            $this->swap($other);
        }

        if ($other->start < $this->end) {
            if ($other->end < $this->end) {
                $start = $other->end;
                $end = $this->end;
                $other->end = $start;
                $this->end = $other->start - self::SECONDS_IN_DAY;

                return new Range($start + self::SECONDS_IN_DAY, $end);
            }

            $start = $this->end;
            $end = $other->end;
            $this->end = $other->start - self::SECONDS_IN_DAY;
            $other->end = $start;

            return new Range($start + self::SECONDS_IN_DAY, $end);
        }

        return null;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    private function swap(Range $other): void
    {
        $start = $this->start;
        $end = $this->end;
        $this->start = $other->start;
        $this->end = $other->end;
        $other->start = $start;
        $other->end = $end;
    }
}
