<?php


use Faker\Factory;
use Laudis\Fiscal\IndexType;
use Phinx\Seed\AbstractSeed;

final class IndexedValueVersionSeed extends AbstractSeed
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
            [IndexedValueSeed::class]
        );
    }

    public function run(): void
    {
        $ivs = $this->query(<<<SQL
SELECT iv.slug as slug,
        iv.id as id,
        iv.original_name as name,
        iv.type as type
FROM indexed_values iv
    JOIN indexed_value_group_pivot ivgp on iv.id = ivgp.indexed_value_id
    JOIN indexed_value_groups ivg on ivgp.group_id = ivg.id
    WHERE ivg.slug = 'auto-generated'
SQL)->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ivs as $iv) {
            if ($iv['type'] === IndexType::PERCENTAGE()->getValue()) {
                $value = $this->generatePercentage();
            } elseif ($iv['type'] === IndexType::EURO()->getValue()) {
                $value = $this->generateEuro();
            } else {
                $value = $this->generateConstant();
            }

            $data = [];
            for ($i = 0; $i < 100; ++$i) {
                $value *= $this->faker->randomFloat(4, 0.99, 1.04);
                $data[] = [
                    'name' => $this->faker->numberBetween(0, 100) === 0 ? $this->faker->words($this->faker->numberBetween(1, 5), true) : null,
                    'value' => $value,
                    'added_since' => (2000 + $i) . '-01-01',
                    'removed_since' => (2000 + $i) . '-12-31',
                    'contextual_id' => $iv['id']
                ];
            }

            $this->table('indexed_value_versions')->insert($data)->save();
        }
    }

    private function generatePercentage(): float
    {
        return $this->faker->randomFloat(4, 0, 1);
    }

    private function generateEuro(): float
    {
        return $this->faker->randomFloat(2, 0, 10000000);
    }

    private function generateConstant(): float
    {
        return $this->faker->randomFloat(0, 0, 1000000);
    }


}
