<?php

namespace App\DataFixtures;

use App\Entity\Restaurant;
use DateTimeImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Faker;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class RestaurantFixtures extends Fixture implements DependentFixtureInterface
{
    public const RESTAURANT_REFERENCE = 'restaurant-';
    public const RESTAURANT_NB_TUPLES = 10;

    /** @throws Exception */
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create();

        for ($i = 1; $i <= self::RESTAURANT_NB_TUPLES; $i++) {
            $restaurant = (new Restaurant())
                ->setName("Restaurant n°$i")
                ->setDescription($faker->text())
                ->setAmOpeningTime([])
                ->setPmOpeningTime([])
                ->setMaxGuest(random_int(10, 50));

            $manager->persist($restaurant);
            $this->addReference(self::RESTAURANT_REFERENCE . $i, $restaurant);
        }

        $manager->flush();
    }

    public function getDependencies()
    {
        return [UserFixtures::class];
    }
}
