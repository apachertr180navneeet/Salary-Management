<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the dashboard page is accessible and displays seeded employees.
     */
    public function test_hr_manager_can_view_employee_directory(): void
    {
        // Seed some employees
        Employee::factory()->create([
            'first_name' => 'Sarah',
            'last_name' => 'Connor',
            'email' => 'sarah.connor@sky.net',
            'job_title' => 'Security Specialist',
            'country' => 'United States',
            'department' => 'Human Resources',
            'salary' => 95000.00,
            'hire_date' => '2023-01-15'
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Sarah Connor');
        $response->assertSee('Security Specialist');
        $response->assertSee('Human Resources');
        $response->assertSee('United States');
        $response->assertSee('$95,000.00');
    }

    /**
     * Test that searches filter results correctly.
     */
    public function test_hr_manager_can_search_employees_by_name_or_email(): void
    {
        $employeeA = Employee::factory()->create([
            'first_name' => 'Bruce',
            'last_name' => 'Wayne',
            'email' => 'bruce@waynecorp.com'
        ]);

        $employeeB = Employee::factory()->create([
            'first_name' => 'Clark',
            'last_name' => 'Kent',
            'email' => 'clark@dailyplanet.com'
        ]);

        // Search for Bruce
        $response = $this->get('/?search=Bruce', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $response->assertStatus(200);
        $response->assertSee('Bruce Wayne');
        $response->assertDontSee('Clark Kent');

        // Search for Clark's email
        $responseEmail = $this->get('/?search=dailyplanet', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);

        $responseEmail->assertStatus(200);
        $responseEmail->assertSee('Clark Kent');
        $responseEmail->assertDontSee('Bruce Wayne');
    }

    /**
     * Test that employees can be filtered by country, department and job title.
     */
    public function test_hr_manager_can_filter_employees_by_attributes(): void
    {
        Employee::factory()->create([
            'first_name' => 'Tony',
            'last_name' => 'Stark',
            'country' => 'United States',
            'department' => 'Engineering',
            'job_title' => 'Software Engineer'
        ]);

        Employee::factory()->create([
            'first_name' => 'Pepper',
            'last_name' => 'Potts',
            'country' => 'United Kingdom',
            'department' => 'Finance',
            'job_title' => 'Financial Analyst'
        ]);

        // Filter by country
        $responseCountry = $this->get('/?country=United Kingdom', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $responseCountry->assertSee('Pepper Potts');
        $responseCountry->assertDontSee('Tony Stark');

        // Filter by department
        $responseDept = $this->get('/?department=Engineering', [
            'X-Requested-With' => 'XMLHttpRequest'
        ]);
        $responseDept->assertSee('Tony Stark');
        $responseDept->assertDontSee('Pepper Potts');
    }

    /**
     * Test that a new employee can be successfully created.
     */
    public function test_hr_manager_can_create_employee(): void
    {
        $employeeData = [
            'first_name' => 'Peter',
            'last_name' => 'Parker',
            'email' => 'peter.parker@dailybugle.com',
            'job_title' => 'Photographer',
            'country' => 'United States',
            'department' => 'Marketing',
            'salary' => 45000.00,
            'hire_date' => '2024-05-01'
        ];

        $response = $this->post('/employees', $employeeData, [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(201) // 201 Created or 200 OK
                 ->assertJson([
                     'success' => true,
                     'message' => 'Employee created successfully.'
                 ]);

        $this->assertDatabaseHas('employees', [
            'email' => 'peter.parker@dailybugle.com',
            'first_name' => 'Peter',
            'last_name' => 'Parker'
        ]);
    }

    /**
     * Test creation validation rules.
     */
    public function test_employee_creation_fails_with_invalid_data(): void
    {
        // 1. Missing fields
        $responseMissing = $this->post('/employees', [], [
            'Accept' => 'application/json'
        ]);
        $responseMissing->assertStatus(422)
                        ->assertJsonValidationErrors(['first_name', 'last_name', 'email', 'salary']);

        // 2. Duplicate email
        Employee::factory()->create(['email' => 'duplicate@org.com']);
        
        $employeeData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'duplicate@org.com',
            'job_title' => 'Staff Accountant',
            'country' => 'Canada',
            'department' => 'Finance',
            'salary' => 60000.00,
            'hire_date' => '2025-01-01'
        ];

        $responseDup = $this->post('/employees', $employeeData, [
            'Accept' => 'application/json'
        ]);
        $responseDup->assertStatus(422)
                    ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test that an existing employee can be updated.
     */
    public function test_hr_manager_can_update_employee(): void
    {
        $employee = Employee::factory()->create([
            'first_name' => 'Diana',
            'last_name' => 'Prince',
            'email' => 'diana@amazon.com',
            'salary' => 120000.00
        ]);

        $updateData = [
            'first_name' => 'Diana',
            'last_name' => 'Prince-Wayne',
            'email' => 'diana@waynecorp.com', // changed email
            'job_title' => 'Vice President',
            'country' => 'United States',
            'department' => 'Sales',
            'salary' => 150000.00, // increased salary
            'hire_date' => '2020-03-10'
        ];

        $response = $this->put("/employees/{$employee->id}", $updateData, [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Employee updated successfully.'
                 ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'last_name' => 'Prince-Wayne',
            'email' => 'diana@waynecorp.com',
            'salary' => 150000.00
        ]);
    }

    /**
     * Test that an employee record can be deleted.
     */
    public function test_hr_manager_can_delete_employee(): void
    {
        $employee = Employee::factory()->create();

        $response = $this->delete("/employees/{$employee->id}", [], [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'Employee deleted successfully.'
                 ]);

        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id
        ]);
    }
}
