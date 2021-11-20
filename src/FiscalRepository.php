<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <https://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Fiscal;

use DateTime;
use DateTimeInterface;
use Ds\Map;
use Ds\Set;
use Ds\Vector;
use Exception;
use JsonException;
use PDO;
use PDOException;
use UnexpectedValueException;

/**
 * @psalm-type IndexResult = array{id: numeric, slug: string, name_override: string|null, type: string, start: string, end: string, name: string, value: float, precision: int}
 * @psalm-type ScaleResult = array{id: numeric, slug: string, name: string, factor: string|null, upperLimit: string|null}
 */
final class FiscalRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param iterable<string> $slugs
     *
     * @throws Exception
     *
     * @return Map<string, Scale>
     */
    public function loadScalesWithSlugs(DateTimeInterface|int|string $timestamp, iterable $slugs): Map
    {
        $context = $this->timestampToContext($timestamp);
        $results = $this->fetchIdResults($context, $slugs);
        $idsToIndex = $this->pullIndices($results, $context);

        /** @var Map<string, Scale> $map */
        $map = new Map();
        foreach ($results as $result) {
            $scale = $map->get($result['slug'], null);
            if ($scale === null) {
                $scale = new Scale((int) $result['id'], $result['slug'], []);
                $map->put($result['slug'], $scale);
            }
            $upperLimit = $result['upperLimit'];
            $factor = $result['factor'];
            $scale->addScaleRule(ScaleRule::make(
                $upperLimit === null ? null : ($idsToIndex[(int) $upperLimit] ?? null),
                $factor === null ? null : ($idsToIndex[(int) $factor] ?? null)
            ));
        }

        return $map;
    }

    /**
     * @throws Exception
     */
    private function timestampToContext(string|int|DateTimeInterface $timestamp): string
    {
        if (is_string($timestamp)) {
            return $timestamp;
        }

        if (is_int($timestamp)) {
            return (new DateTime('@'.$timestamp))->format('Y-m-d');
        }

        return $timestamp->format('Y-m-d');
    }

    /**
     * @param iterable<string> $slugs
     *
     * @throws JsonException
     * @throws Exception
     *
     * @return Map<string, IndexedValue>
     */
    public function loadIndexedValuesWithSlugs(string|int|DateTimeInterface $dateTime, iterable $slugs): Map
    {
        $context = $this->timestampToContext($dateTime);
        $values = $this->pullSlugResults($slugs, $context);

        /** @var Map<string, IndexedValue> $tbr */
        $tbr = new Map();
        foreach ($this->interpretResults($values) as $result) {
            $tbr->put($result->getSlug(), $result);
        }

        return $tbr;
    }

    /**
     * @param string|DateTimeInterface|int $dateTime
     * @param iterable<int>                $ids
     *
     * @throws Exception
     *
     * @return Map<int, IndexedValue>
     */
    public function loadIndexedValuesWithIds($dateTime, iterable $ids): Map
    {
        $context = $this->timestampToContext($dateTime);
        $vector = new Vector($ids);
        $in = str_repeat('?,', ($vector)->count() - 1).'?';

        $statement = $this->pdo->prepare(<<<SQL
SELECT iv.id as id,
       iv.`precision` as `precision`,
       iv.slug as slug,
       iv.original_name as name,
       iv.type as type,
       ivv.value as value,
       ivv.added_since as start,
       ivv.name as name_override
    FROM indexed_values iv
    JOIN indexed_value_versions ivv on iv.id = ivv.contextual_id
    WHERE iv.id IN ($in)
    AND ivv.added_since <= ? AND ivv.removed_since >= ?
SQL
        );

        $statement->execute($vector->merge([$context, $context])->toArray());

        /** @var array<int, IndexResult>|false $results */
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($results === false) {
            throw new PDOException(json_encode($this->pdo->errorInfo(), JSON_THROW_ON_ERROR));
        }

        return $this->interpretResults($results);
    }

    /**
     * @param array<int, IndexResult> $values
     *
     * @return Map<int, IndexedValue>
     */
    private function interpretResults(array $values): Map
    {
        /** @var Map<int, IndexResult> $map */
        $map = new Map();
        foreach ($values as $index) {
            $value = $map->get((int) $index['id'], null);
            if ($value === null ||
                DateTime::createFromFormat('Y-m-d', $index['start']) > DateTime::createFromFormat('Y-m-d', $value['start'])
            ) {
                $map->put((int) $index['id'], $index);
            }
        }

        /** @psalm-suppress UnusedClosureParam */
        return $map->map(static function (int $key, array $value) {
            $indexTypes = IndexType::resolve($value['type']);
            if (!isset($indexTypes[0])) {
                throw new UnexpectedValueException('Expected to receive a valid type');
            }

            return new IndexedValue(
                (int) $value['id'],
                $value['slug'],
                $value['name_override'] ?? $value['name'],
                $value['value'],
                $indexTypes[0],
                $value['precision']
            );
        });
    }

    /**
     * @param iterable<string> $slugs
     *
     * @throws Exception
     *
     * @return array<int, ScaleResult>
     */
    private function fetchIdResults(string $date, iterable $slugs): array
    {
        $vector = new Vector($slugs);
        $in = str_repeat('?,', ($vector)->count() - 1).'?';

        $statement = $this->pdo->prepare(<<<SQL
SELECT  scales.name as name,
        scales.slug as slug,
        scales.id as id,
        scale_rules.factor as factor,
        scale_rules.upper_limit as upperLimit
FROM scales
JOIN scale_rules ON scales.id = scale_rules.scale_id
WHERE   scales.slug IN ($in) AND
        scale_rules.start <= ? AND
        scale_rules.end >= ?
SQL
        );
        $statement->execute($vector->merge([$date, $date])->toArray());

        /** @var array<int, ScaleResult>|false $results */
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($results === false) {
            throw new PDOException(json_encode($this->pdo->errorInfo(), JSON_THROW_ON_ERROR));
        }

        return $results;
    }

    /**
     * @param array<int, array{name: string, slug: string, factor: null|string, upperLimit: null|string}> $results
     *
     * @throws Exception
     *
     * @return Map<int, IndexedValue>
     */
    private function pullIndices(array $results, string $timestamp): Map
    {
        /** @var Set<int> $ids */
        $ids = new Set();
        foreach ($results as $result) {
            $factor = $result['factor'];
            if ($factor !== null) {
                $ids->add((int) $factor);
            }
            $upperLimit = $result['upperLimit'];
            if ($upperLimit !== null) {
                $ids->add((int) $upperLimit);
            }
        }

        return $this->loadIndexedValuesWithIds($timestamp, $ids);
    }

    /**
     * @param iterable<string> $slugs
     *
     * @throws JsonException
     *
     * @return array<int, IndexResult>
     */
    private function pullSlugResults(iterable $slugs, string $context): array
    {
        $vector = new Vector($slugs);
        $in = str_repeat('?,', ($vector)->count() - 1).'?';

        $statement = $this->pdo->prepare(<<<SQL
SELECT iv.id as id,
       iv.precision as `precision`,
       iv.slug as slug,
       iv.original_name as name,
       iv.type as type,
       ivv.value as value,
       ivv.added_since as start,
       ivv.removed_since as end,
       ivv.name as name_override
    FROM indexed_values iv
    JOIN indexed_value_versions ivv on iv.id = ivv.contextual_id
    WHERE iv.slug IN ($in)
    AND ivv.added_since <= ? AND ivv.removed_since >= ?
SQL
        );
        $statement->execute($vector->merge([$context, $context])->toArray());

        /** @var array<int, IndexResult>|false $results */
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);
        if ($results === false) {
            throw new PDOException(json_encode($this->pdo->errorInfo(), JSON_THROW_ON_ERROR));
        }

        return $results;
    }

    /**
     * @param iterable<string> $slugs
     *
     * @return Map<string, Vector<Range>>
     */
    public function loadVersions(iterable $slugs): Map
    {
        $vector = new Vector($slugs);
        $results = $this->loadScalesWithRules($vector);

        /** @var Map<string, Vector<Range>> $scaleRanges */
        $scaleRanges = new Map();
        /** @var Map<string, Set<int>> $scaleToIndexedValueMapping */
        $scaleToIndexedValueMapping = new Map();
        /** @var Map<int, Vector<Range>> $indexedValueRanges */
        $indexedValueRanges = new Map();

        foreach ($results as $result) {
            $this->fillIndexedValueIds($scaleToIndexedValueMapping, $result, $indexedValueRanges);
            $this->fillRuleRanges($scaleRanges, $result);
        }

        $results = $this->loadIndexedValuesWithVersions($indexedValueRanges->keys());
        foreach ($results as $result) {
            $this->fillIndexedValueRanges($indexedValueRanges, $result);
        }

        $this->limitAndMergeRanges($scaleRanges, $scaleToIndexedValueMapping, $indexedValueRanges);

        return $scaleRanges;
    }

    /**
     * @return callable(Range):bool
     */
    private function validRangePredicate(): callable
    {
        return static fn (Range $x) => $x->getStart() < $x->getEnd();
    }

    /**
     * @return callable(Range):bool
     */
    private function duplicateRangePredicate(): callable
    {
        /** @var Set<string> $set */
        $set = new Set();

        return static function (Range $x) use ($set) {
            $key = $x->getStart().':'.$x->getEnd();
            if (!$set->contains($key)) {
                $set->add($key);

                return true;
            }

            return false;
        };
    }

    /**
     * @param Vector<Range> $ranges
     *
     * @return Vector<Range>
     */
    private function sortAndSplitRanges(Vector $ranges): Vector
    {
        $ranges = $ranges
            ->filter($this->validRangePredicate())
            ->filter($this->duplicateRangePredicate())
        ;

        /** @var Vector<Range> $tbr */
        $tbr = new Vector();
        foreach ($ranges as $newRange) {
            for ($i = 0; $i < $tbr->count(); ++$i) {
                $range = $tbr->get($i);
                if ($newRange->getStart() >= $newRange->getEnd()) {
                    break;
                }
                $otherRange = $range->sortAndSplit($newRange);
                if ($otherRange !== null) {
                    if ($i + 1 >= $tbr->count()) {
                        $tbr->push($newRange);
                    } else {
                        $tbr->insert($i + 1, $newRange);
                    }
                    ++$i;
                    $newRange = $otherRange;
                }
            }
            if ($newRange->getStart() < $newRange->getEnd()) {
                $tbr->push($newRange);
            }
            $tbr = $tbr->filter($this->validRangePredicate());
        }

        return $tbr;
    }

    /**
     * @param Vector<string> $slugs
     *
     * @return list<array{slug: string, start: string, end: string, factor: float|null, limit: float|null}>
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function loadScalesWithRules(Vector $slugs): array
    {
        $in = str_repeat('?,', ($slugs)->count() - 1).'?';

        $statement = $this->pdo->prepare(<<<SQL
SELECT  scales.slug as slug,
        scale_rules.start as start,
        scale_rules.end as end,
        scale_rules.factor as factor,
        scale_rules.upper_limit as `limit`
FROM scales
JOIN scale_rules ON scales.id = scale_rules.scale_id
WHERE   scales.slug IN ($in)
SQL
        );
        $statement->execute($slugs->toArray());

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param Map<string, Set<int>>                                                                  $scaleToIdMapping
     * @param array{end: string, factor: float|null, slug: string, start: string, limit: float|null} $result
     */
    private function fillIndexedValueIds(Map $scaleToIdMapping, array $result, Map $indexedValueRanges): void
    {
        if (!$scaleToIdMapping->hasKey($result['slug'])) {
            $scaleToIdMapping->put($result['slug'], new Set());
        }

        if ($result['limit'] !== null) {
            $indexedValueRanges->put((int) $result['limit'], new Vector());
            $scaleToIdMapping->get($result['slug'])->add((int) $result['limit']);
        }

        if ($result['factor'] !== null) {
            $indexedValueRanges->put((int) $result['factor'], new Vector());
            $scaleToIdMapping->get($result['slug'])->add((int) $result['factor']);
        }
    }

    /**
     * @param Map<string, Vector<Range>>                                                             $rangesInScale
     * @param array{end: string, factor: float|null, slug: string, start: string, limit: float|null} $result
     */
    private function fillRuleRanges(Map $rangesInScale, $result): void
    {
        $ranges = $rangesInScale->get($result['slug'], null);
        $newRange = Range::fromStringFormat($result['start'], $result['end']);
        if ($ranges === null) {
            /** @var Vector<Range> $ranges */
            $ranges = new Vector();
            $rangesInScale->put($result['slug'], $ranges);
        }
        $ranges->push($newRange);
    }

    /**
     * @param Set<int> $indexedValuesIds
     *
     * @return list<array{id: int, start: string, end: string}>
     *
     * @psalm-suppress MixedReturnTypeCoercion
     */
    private function loadIndexedValuesWithVersions(Set $indexedValuesIds): array
    {
        $in = str_repeat('?,', $indexedValuesIds->count() - 1).'?';

        $statement = $this->pdo->prepare(<<<SQL
SELECT iv.id as id,
       ivv.added_since as start,
       ivv.removed_since as end
FROM indexed_values iv
JOIN indexed_value_versions ivv on iv.id = ivv.contextual_id
WHERE iv.id IN ($in)
SQL
        );
        $statement->execute($indexedValuesIds->toArray());

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param Map<int, Vector<Range>>                        $indexedValueRanges
     * @param array{id: numeric, start: string, end: string} $result
     */
    private function fillIndexedValueRanges(Map $indexedValueRanges, array $result): void
    {
        $ranges = $indexedValueRanges->get((int) $result['id']);
        $newRange = Range::fromStringFormat($result['start'], $result['end']);
        $ranges->push($newRange);
    }

    /**
     * @param Map<string, Vector<Range>> $scaleRanges
     * @param Map<string, Set<int>>      $scaleToIndexedValueMapping
     * @param Map<int, Vector<Range>>    $indexedValueRanges
     */
    private function limitAndMergeRanges(Map $scaleRanges, Map $scaleToIndexedValueMapping, Map $indexedValueRanges): void
    {
        foreach ($scaleRanges as $slug => $ranges) {
            $min = PHP_INT_MAX;
            $max = PHP_INT_MIN;
            foreach ($ranges as $range) {
                $min = min($min, $range->getStart());
                $max = max($max, $range->getEnd());
            }

            foreach ($scaleToIndexedValueMapping->get($slug) as $id) {
                foreach ($indexedValueRanges->get($id) as $range) {
                    $ranges->push(new Range(max($min, $range->getStart()), min($max, $range->getEnd())));
                }
            }
            $scaleRanges->put($slug, $this->sortAndSplitRanges($ranges));
        }
    }
}
