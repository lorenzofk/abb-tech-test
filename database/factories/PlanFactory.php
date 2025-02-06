<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->sentence(rand(1, 3)),
            'type' => $this->faker->randomElement(['nbn', 'opticomm', 'mobile']),
            'monthly_cost' => $this->faker->numerify('####'),
        ];
    }

    /**
     * Return a plan with the type of `opticomm`.
     */
    public function opticomm(): self
    {
        return $this->state(fn () => ['type' => 'opticomm']);
    }

    /**
     * Return a plan with the type of `mobile`.
     */
    public function mobile(): self
    {
        return $this->state(fn () => ['type' => 'mobile']);
    }

    /**
     * Return a plan with the type of `nbn`.
     */
    public function nbn(): self
    {
        return $this->state(fn () => ['type' => 'nbn']);
    }
}
