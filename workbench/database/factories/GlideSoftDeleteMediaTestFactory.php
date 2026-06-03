<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\GlideSoftDeleteMediaTest;

class GlideSoftDeleteMediaTestFactory extends Factory
{
    protected $model = GlideSoftDeleteMediaTest::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->slug(),
        ];
    }
}
