<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\SoftDeleteMediaTest;

class SoftDeleteMediaTestFactory extends Factory
{
    protected $model = SoftDeleteMediaTest::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->slug(),
        ];
    }
}
