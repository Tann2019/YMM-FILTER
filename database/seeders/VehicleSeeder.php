<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vehicle;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vehicles = [
            // Ford F-150
            ['year_start' => 2015, 'year_end' => 2020, 'make' => 'Ford', 'model' => 'F-150'],
            ['year_start' => 2021, 'year_end' => 2023, 'make' => 'Ford', 'model' => 'F-150'],
            
            // Chevrolet Silverado
            ['year_start' => 2014, 'year_end' => 2018, 'make' => 'Chevrolet', 'model' => 'Silverado 1500'],
            ['year_start' => 2019, 'year_end' => 2023, 'make' => 'Chevrolet', 'model' => 'Silverado 1500'],
            
            // Ram 1500
            ['year_start' => 2013, 'year_end' => 2018, 'make' => 'Ram', 'model' => '1500'],
            ['year_start' => 2019, 'year_end' => 2023, 'make' => 'Ram', 'model' => '1500'],
            
            // Toyota Tacoma
            ['year_start' => 2016, 'year_end' => 2023, 'make' => 'Toyota', 'model' => 'Tacoma'],
            
            // GMC Sierra
            ['year_start' => 2014, 'year_end' => 2018, 'make' => 'GMC', 'model' => 'Sierra 1500'],
            ['year_start' => 2019, 'year_end' => 2023, 'make' => 'GMC', 'model' => 'Sierra 1500'],
            
            // Honda Ridgeline
            ['year_start' => 2017, 'year_end' => 2023, 'make' => 'Honda', 'model' => 'Ridgeline'],
            
            // Nissan Titan
            ['year_start' => 2016, 'year_end' => 2023, 'make' => 'Nissan', 'model' => 'Titan'],
            
            // Ford Ranger
            ['year_start' => 2019, 'year_end' => 2023, 'make' => 'Ford', 'model' => 'Ranger'],
            
            // Chevrolet Colorado
            ['year_start' => 2015, 'year_end' => 2023, 'make' => 'Chevrolet', 'model' => 'Colorado'],
            
            // GMC Canyon
            ['year_start' => 2015, 'year_end' => 2023, 'make' => 'GMC', 'model' => 'Canyon'],
            
            // Jeep Gladiator
            ['year_start' => 2020, 'year_end' => 2023, 'make' => 'Jeep', 'model' => 'Gladiator'],
        ];

        foreach ($vehicles as $vehicle) {
            Vehicle::create($vehicle);
        }
    }
}
