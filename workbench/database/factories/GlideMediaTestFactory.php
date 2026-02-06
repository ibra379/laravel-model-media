<?php

namespace Workbench\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Workbench\App\Models\GlideMediaTest;

class GlideMediaTestFactory extends Factory
{
    protected $model = GlideMediaTest::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->slug(),
        ];
    }
}
