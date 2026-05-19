<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Database\Seeders\EmployeeSeeder;

class EmployeeSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the seeder populates exactly 10,000 employees and all properties are set.
     */
    public function test_employee_seeder_creates_exactly_ten_thousand_unique_records(): void
    {
        // Assert table is empty
        $this->assertEquals(0, Employee::count());

        // Run the seeder
        $this->seed(EmployeeSeeder::class);

        // Assert exactly 10,000 employees are created
        $this->assertEquals(10000, Employee::count());

        // Assert that all attributes are correctly populated
        $employee = Employee::first();
        $this->assertNotNull($employee->first_name);
        $this->assertNotNull($employee->last_name);
        $this->assertNotNull($employee->email);
        $this->assertNotNull($employee->job_title);
        $this->assertNotNull($employee->country);
        $this->assertNotNull($employee->department);
        $this->assertNotNull($employee->salary);
        $this->assertNotNull($employee->hire_date);

        // Assert that the email matches our corporate pattern
        $this->assertStringContainsString('@organization.com', $employee->email);

        // Assert that emails are indeed unique
        $uniqueEmailsCount = Employee::distinct()->count('email');
        $this->assertEquals(10000, $uniqueEmailsCount);

        // Assert salary bounds are realistic
        $this->assertGreaterThanOrEqual(30000, $employee->salary);
        
        // Assert that seeded countries match our list of predefined regions
        $countriesList = ['United States', 'United Kingdom', 'Canada', 'India', 'Germany', 'Australia', 'Singapore', 'United Arab Emirates'];
        $this->assertContains($employee->country, $countriesList);
    }
}
