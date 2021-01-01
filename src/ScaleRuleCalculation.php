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

use Ds\Vector;

/**
 * @psalm-import-type Rule from Scale
 */
final class ScaleRuleCalculation
{
    private ?ScaleRuleCalculation $previous;
    private ScaleRule $rule;

    public function __construct(?ScaleRuleCalculation $previous, ScaleRule $rule)
    {
        $this->previous = $previous;
        $this->rule = $rule;
    }

    public function calculate(float $value): float
    {
        if ($this->previous === null) {
            $previousLimit = 0.0;
        } else {
            $previousLimit = ScaleRule::LIMIT($this->previous->rule);
        }
        $limit = ScaleRule::LIMIT($this->rule);
        $factor = ScaleRule::FACTOR($this->rule);

        $tbr = max(min($limit, $value) - $previousLimit, 0) * $factor;
        if ($this->previous) {
            $tbr += $this->previous->calculate($value);
        }

        return $tbr;
    }

    /**
     * @return Vector<Rule>
     */
    public function explain(float $value): Vector
    {
        $tbr = new Vector();
        $current = $this;

        do {
            $tbr->push($this->presentRule($current, $value));
            $current = $current->previous;
        } while ($current !== null);

        $tbr->reverse();

        $aggregation = 0.0;
        foreach ($tbr as $i => $results) {
            $results['aggregated'] = $aggregation;
            $tbr->set($i, $results);
            $aggregation += ($results['ruleResult'] ?? 0.0);
        }

        return $tbr;
    }

    /**
     * @return Rule
     */
    private function presentRule(ScaleRuleCalculation $current, float $value): array
    {
        $limit = $current->rule->getLimit();
        $factor = $current->rule->getFactor();

        $limitId = $limit ? $limit->getId() : null;
        $factorId = $factor ? $factor->getId() : null;
        $limitValue = ScaleRule::LIMIT($current->rule);
        $limitedValue = min($limitValue, $value);
        $previousLimit = $current->previous ? ScaleRule::LIMIT($current->previous->rule) : 0.0;
        $netValue = max($limitedValue - $previousLimit, 0.0);
        $factorValue = ScaleRule::FACTOR($current->rule);
        $ruleResult = $netValue * $factorValue;
        $aggregated = 0.0;

        return array_merge(
            compact('limitId', 'factorId', 'limitValue', 'limitedValue', 'aggregated'),
            compact('previousLimit', 'netValue', 'factorValue', 'ruleResult')
        );
    }
}
