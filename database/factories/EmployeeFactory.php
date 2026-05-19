<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $firstName = $this->faker->firstName();
        $lastName = $this->faker->lastName();
        
        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => strtolower($firstName . '.' . $lastName . rand(10, 999) . '@testing.com'),
            'job_title' => $this->faker->randomElement(['Software Engineer', 'Senior Software Engineer', 'Product Manager', 'Financial Analyst', 'HR Director']),
            'country' => $this->faker->randomElement(['United States', 'United Kingdom', 'Canada', 'India', 'Germany']),
            'department' => $this->faker->randomElement(['Engineering', 'Product', 'Sales', 'Marketing', 'Human Resources', 'Finance']),
            'salary' => $this->faker->randomFloat(2, 40000, 200000),
            'hire_date' => $this->faker->date('Y-m-d'),
        ];
    }
}
