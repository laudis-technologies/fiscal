<?php


use Cocur\Slugify\Slugify;
use Faker\Factory;
use Phinx\Seed\AbstractSeed;
use Symfony\Component\Uid\UuidV4;

final class ScaleSeed extends AbstractSeed
{
    private Slugify $slugify;
    private \Faker\Generator $faker;

    public function __construct()
    {
        parent::__construct();
        $this->slugify = new Slugify();
        $this->faker = Factory::create('nl_BE');
    }

    public function run(): void
    {
        $data = [];
        for ($i = 0; $i < 100; ++$i) {
            $name = $this->faker->words($this->faker->numberBetween(1, 5), true);
            $data[] = [
                'name' => $name,
                'slug' => $this->slugify->slugify($name) . '-' . UuidV4::v4()->toRfc4122(),
            ];
        }
        $this->table('scales')->insert($data)->save();
    }
}
