<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryInsightsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that global salary aggregations match expectations.
     */
    public function test_global_insights_aggregations_are_mathematically_correct(): void
    {
        // Seed deterministic salaries
        Employee::factory()->create(['salary' => 50000.00]);
        Employee::factory()->create(['salary' => 100000.00]);
        Employee::factory()->create(['salary' => 150000.00]);

        $response = $this->get('/salary-insights', [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200);
        $this->assertEquals(3, $response->json('globalStats.total_count'));
        $this->assertEquals(300000.00, floatval($response->json('globalStats.total_budget')));
        $this->assertEquals(100000.00, floatval($response->json('globalStats.avg_salary')));
        $this->assertEquals(50000.00, floatval($response->json('globalStats.min_salary')));
        $this->assertEquals(150000.00, floatval($response->json('globalStats.max_salary')));
    }

    /**
     * Test country-specific salary aggregates (MIN, MAX, AVG).
     */
    public function test_country_salary_aggregations_are_correct(): void
    {
        // Country A
        Employee::factory()->create(['country' => 'Canada', 'salary' => 80000.00]);
        Employee::factory()->create(['country' => 'Canada', 'salary' => 120000.00]);

        // Country B
        Employee::factory()->create(['country' => 'Germany', 'salary' => 90000.00]);

        $response = $this->get('/salary-insights', [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200);

        // Fetch countryStats array from response
        $countryStats = $response->json('countryStats');
        
        $this->assertCount(2, $countryStats);

        // Canada stats check
        $canada = collect($countryStats)->firstWhere('country', 'Canada');
        $this->assertEquals(2, $canada['count']);
        $this->assertEquals(80000.00, $canada['min_salary']);
        $this->assertEquals(120000.00, $canada['max_salary']);
        $this->assertEquals(100000.00, $canada['avg_salary']);

        // Germany stats check
        $germany = collect($countryStats)->firstWhere('country', 'Germany');
        $this->assertEquals(1, $germany['count']);
        $this->assertEquals(90000.00, $germany['avg_salary']);
    }

    /**
     * Test average salary for a given Job Title in a specific Country.
     */
    public function test_job_title_average_salary_per_country_is_correct(): void
    {
        // India Software Engineers
        Employee::factory()->create([
            'country' => 'India',
            'job_title' => 'Software Engineer',
            'salary' => 40000.00
        ]);
        Employee::factory()->create([
            'country' => 'India',
            'job_title' => 'Software Engineer',
            'salary' => 60000.00
        ]);

        // India Tech Lead
        Employee::factory()->create([
            'country' => 'India',
            'job_title' => 'Tech Lead',
            'salary' => 110000.00
        ]);

        // US Software Engineer (must not mix into India averages)
        Employee::factory()->create([
            'country' => 'United States',
            'job_title' => 'Software Engineer',
            'salary' => 140000.00
        ]);

        // Dynamic API request for India focus
        $response = $this->get('/salary-insights?insight_country=India', [
            'Accept' => 'application/json'
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('selectedCountry', 'India');

        $jobStats = $response->json('countryJobStats');

        // India Software Engineer Average must be $50,000 (avg of 40k and 60k)
        $se = collect($jobStats)->firstWhere('job_title', 'Software Engineer');
        $this->assertEquals(2, $se['count']);
        $this->assertEquals(50000.00, $se['avg_salary']);
        $this->assertEquals(40000.00, $se['min_salary']);
        $this->assertEquals(60000.00, $se['max_salary']);

        // India Tech Lead Average must be $110,000
        $tl = collect($jobStats)->firstWhere('job_title', 'Tech Lead');
        $this->assertEquals(1, $tl['count']);
        $this->assertEquals(110000.00, $tl['avg_salary']);
    }
}
