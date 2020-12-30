<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Faker\Factory;
use Phinx\Seed\AbstractSeed;

final class ScaleRuleSeed extends AbstractSeed
{
    private \Faker\Generator $faker;

    public function __construct()
    {
        parent::__construct();
        $this->faker = Factory::create('nl_BE');
    }

    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [
                IndexedValueVersionSeed::class,
                ScaleSeed::class,
            ]
        );
    }

    public function run(): void
    {
        $ivs = $this->pullIndexedValues();
        $scales = $this->query('SELECT * FROM scales')->fetchAll(PDO::FETCH_ASSOC);
        $emptyStart = $this->faker->numberBetween(0, 4) === 0;
        $emptyEnd = $this->faker->boolean;
        $rowCount = $this->faker->numberBetween(2, 10);

        foreach ($scales as $scale) {
            $data = [];
            $rows = [];
            $euros = $ivs['euro'];
            $percentages = $ivs['percentage'];
            for ($i = 0; $i < $rowCount; ++$i) {
                $factor = $i === 0 && $emptyStart ? null : $euros[$this->faker->numberBetween(0, count($euros) - 1)]['id'];
                $limit = $i === ($rowCount - 1) && $emptyEnd ? null : $percentages[$this->faker->numberBetween(0, count($percentages))]['id'];
                $data[] = [
                    'start' => '2000-01-01',
                    'end' => '2099-12-31',
                    'upper_limit' => $limit,
                    'factor' => $factor,
                    'scale_id' => $scale['id'],
                ];
                $rows[] = count($data) - 1;
            }

            for ($i = 1; $i < 100; ++$i) {
                $createRow = $this->faker->numberBetween(0, 3) === 0;
                if ($createRow) {
                    $data[] = [
                        'start' => (2000 + $i).'-01-01',
                        'end' => (2099 - 12 - 31).'-01-01',
                        'upper_limit' => $euros[$this->faker->numberBetween(0, count($euros) - 1)]['id'],
                        'factor' => $percentages[$this->faker->numberBetween(0, count($percentages))]['id'],
                        'scale_id' => $scale['id'],
                    ];
                    $rows[] = count($data) - 1;
                }

                $removeRow = $this->faker->numberBetween(0, 3) === 0;
                if ($removeRow && count($rows) > 2) {
                    $toRemove = $this->faker->numberBetween(0, count($rows) - 1);
                    $data[$rows[$toRemove]]['end'] = (2000 + $i).'-12-31';
                    array_splice($rows, $toRemove, 1);
                }
            }
            $this->table('scale_rules')->insert($data)->save();
        }
    }

    private function pullIndexedValues(): array
    {
        $tbr = [
            'percentage' => [],
            'euro' => [],
        ];
        $results = $this->query(<<<SQL
SELECT iv.slug as slug,
        iv.id as id,
        iv.original_name as name,
        iv.type as type
FROM indexed_values iv
    JOIN indexed_value_group_pivot ivgp on iv.id = ivgp.indexed_value_id
    JOIN indexed_value_groups ivg on ivgp.group_id = ivg.id
    WHERE ivg.slug = 'auto-generated'
SQL
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($results as $result) {
            $tbr[$result['type']][] = $result;
        }

        return $tbr;
    }
}
