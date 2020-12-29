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

use Ds\Map;
use Ds\Vector;

/**
 * @psalm-type Rule = array{limitId: int|null, factorId: int|null, limitValue: float, limitedValue: float, previousLimit: float, netValue: float, factorValue: float, ruleResult: float, aggregated: float}
 * @psalm-type Index = array{id: int, slug: string, name: string, value: float, precision: int}
 * @psalm-type Explanation = array{scale: Vector<Rule>, indexedValues: Map<int, Index>,input: float, output: float}
 */
final class Scale
{
    /** @var Vector<ScaleRule> */
    private Vector $rules;
    private bool $sorted = false;
    private int $id;
    private string $slug;

    /**
     * @param iterable<ScaleRule> $rules
     */
    public function __construct(int $id, string $slug, iterable $rules)
    {
        $this->rules = new Vector($rules);
        $this->id = $id;
        $this->slug = $slug;
    }

    public function addScaleRule(ScaleRule $rule): void
    {
        $this->sorted = false;
        $this->rules->push($rule);
    }

    public function calculate(float $value): float
    {
        $current = $this->wrapCalculation();

        return $current !== null ? $current->calculate($value) : 0.0;
    }

    /**
     * @return Vector<ScaleRule>
     */
    public function getRules(): Vector
    {
        $this->sortRules();

        return $this->rules;
    }

    private function sortRules(): void
    {
        if (!$this->sorted) {
            $this->rules->sort(static fn (ScaleRule $x, ScaleRule $y) => ScaleRule::limit($x) <=> ScaleRule::limit($y));
            $this->sorted = true;
        }
    }

    /**
     * @return Explanation
     */
    public function explain(float $value): array
    {
        /** @var Map<int, Index> $values */
        $values = new Map();
        foreach ($this->rules as $rule) {
            $factor = $rule->getFactor();
            $limit = $rule->getLimit();
            if ($factor) {
                $values->put($factor->getId(), $this->presentIndexedValue($factor));
            }
            if ($limit) {
                $values->put($limit->getId(), $this->presentIndexedValue($limit));
            }
        }
        $calculation = $this->wrapCalculation();

        /** @var Vector<Rule> $vector */
        $vector = new Vector();

        return [
            'scale' => $calculation === null ? $vector : $calculation->explain($value),
            'slug' => $this->slug,
            'id' => $this->id,
            'indexedValues' => $values,
            'input' => $value,
            'output' => $this->calculate($value),
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    private function wrapCalculation(): ?ScaleRuleCalculation
    {
        $this->sortRules();

        return $this->rules->reduce(static function (?ScaleRuleCalculation $carry, ScaleRule $rule) {
            return new ScaleRuleCalculation($carry, $rule);
        });
    }

    /**
     * @return Index
     */
    private function presentIndexedValue(IndexedValue $factor): array
    {
        return [
            'id' => $factor->getId(),
            'slug' => $factor->getSlug(),
            'value' => $factor->getValue(),
            'type' => $factor->getType()->getValue(),
            'name' => $factor->getName(),
            'precision' => $factor->getPrecision(),
        ];
    }
}
