<?php

namespace App\DataFixtures;

use App\Entity\Cinema;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CinemaFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $row = 1;
        $seat = 1;
        for ($room= 1;$room<=2;$room++) {
            for ($i = 1; $i <= 60; $i++) {
                $cinema = new Cinema($room,$row,$seat);
                $manager->persist($cinema);
                $seat++;
                if($i%10==0){
                    $row++;
                    $seat = 1;
                }
            }
            $row = 1;
        }
        $manager->flush();

    }
}
