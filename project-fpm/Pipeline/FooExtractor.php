<?php declare(strict_types=1);

namespace Pipeline;

use Faker\Factory;
use Kiboko\Contract\Pipeline\ExtractorInterface;

final class FooExtractor implements ExtractorInterface
{
    public function extract(): iterable
    {
        $faker = Factory::create();
        $faker->addProvider(new \Bezhanov\Faker\Provider\Commerce($faker));
        foreach (range(0, 210) as $index) {
            yield [
                'sku' => $faker->sha1,
                'name' => $faker->productName,
                'description' => $faker->sentences(5, true),
                'shortDescription' => $faker->sentence,
                'usage' => $faker->sentences(12, true),
                'warning' => $faker->sentences(2, true),
                'notice' => $faker->sentences(2, true),
            ];
        }
    }
}
