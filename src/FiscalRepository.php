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
use DateTimeInterface;
use Ds\Map;
use Ds\Set;
use Ds\Vector;
use Exception;
use PDO;
use PDOException;
use UnexpectedValueException;

/**
 * @psalm-type IndexResult = array{id: string, slug: string, name_override: string|null, type: string, start: string, end: string, name: string, value: float}
 * @psalm-type ScaleResult = array{id: string, slug: string, name: string, factor: string|null, upperLimit: string|null}
 */
final class FiscalRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param string|int|DateTimeInterface $timestamp
     * @param iterable<string>             $slugs
     *
     * @throws Exception
     *
     * @return Map<string, Scale>
     */
    public function loadScalesWithSlugs($timestamp, iterable $slugs): Map
    {
        $context = $this->timestampToContext($timestamp);
        $results = $this->fetchIdResults($context, $slugs);
        $idsToIndex = $this->pullIndices($results, $context);

        /** @var Map<string, Scale> $map */
        $map = new Map();
        foreach ($results as $result) {
            $scale = $map->get($result['slug'], null);
            if ($scale === null) {
                $scale = new Scale();
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
     * @param string|int|DateTimeInterface $timestamp
     *
     * @throws Exception
     */
    private function timestampToContext($timestamp): string
    {
        if (is_string($timestamp)) {
            $context = $timestamp;
        } elseif (is_int($timestamp)) {
            $context = (new DateTime('@'.$timestamp))->format('Y-m-d');
        } else {
            $context = $timestamp->format('Y-m-d');
        }

        return $context;
    }

    /**
     * @param string|int|DateTimeInterface $dateTime
     * @param iterable<string>             $slugs
     *
     * @throws Exception
     *
     * @return Map<string, IndexedValue>
     */
    public function loadIndexedValuesWithSlugs($dateTime, iterable $slugs): Map
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

            return IndexedValue::make(
                (int) $value['id'],
                $value['slug'],
                $value['name_override'] ?? $value['name'],
                $value['value'],
                $indexTypes[0],
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
     * @throws \JsonException
     *
     * @return array<int, IndexResult>
     */
    private function pullSlugResults(iterable $slugs, string $context): array
    {
        $vector = new Vector($slugs);
        $in = str_repeat('?,', ($vector)->count() - 1).'?';

        $statement = $this->pdo->prepare(<<<SQL
SELECT iv.id as id,
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
}
