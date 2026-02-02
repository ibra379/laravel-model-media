<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\MediaTest;

class MediaTestFactory extends Factory
{
    protected $model = MediaTest::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->slug(),
        ];
    }
}
