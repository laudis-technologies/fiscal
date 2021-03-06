<?php

/*
 * This file is part of the Laudis Fiscal package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Cocur\Slugify\Slugify;
use Faker\Factory;
use Laudis\Fiscal\IndexType;
use Phinx\Seed\AbstractSeed;
use Symfony\Component\Uid\UuidV4;

final class IndexedValueSeed extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $data = $this->insertIndexedValues();
        $this->connectToAutoGeneratedGroup($data);
    }

    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [IndexedValueGroupSeed::class]
        );
    }

    private function insertIndexedValues(): array
    {
        $faker = Factory::create('nl_BE');
        $slugify = new Slugify();
        $data = [];
        $types = [IndexType::EURO(), IndexType::PERCENTAGE()];

        for ($i = 0; $i < 200; ++$i) {
            $name = $faker->words($faker->numberBetween(1, 5), true);
            $type = $types[$faker->numberBetween(0, 1)];
            $data[] = [
                'original_name' => $name,
                'slug' => $slugify->slugify($name).'-'.UuidV4::v4()->toRfc4122(),
                'type' => $type->getValue(),
                'precision' => IndexType::EURO() === $type ? 2 : 4,
            ];
        }

        $this->table('indexed_values')->insert($data)->save();

        return $data;
    }

    private function connectToAutoGeneratedGroup(array $data): void
    {
        $slugs = array_map(static fn (array $data) => $data['slug'], $data);
        $slugs = '("'.implode('", "', $slugs).'")';
        $ids = $this->query("SELECT id FROM indexed_values WHERE slug IN $slugs")->fetchAll(PDO::FETCH_ASSOC);
        $indexedValueGroup = $this->query('SELECT id FROM indexed_value_groups WHERE slug = "auto-generated"')->fetchAll(PDO::FETCH_ASSOC)[0]['id'];

        $data = [];
        foreach ($ids as $id) {
            $data[] = [
                'indexed_value_id' => $id['id'],
                'group_id' => $indexedValueGroup,
            ];
        }

        $this->table('indexed_value_group_pivot')->insert($data)->save();
    }
}
