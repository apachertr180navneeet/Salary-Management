<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Disable query log to save memory and CPU
        DB::connection()->disableQueryLog();

        // Clear existing employees
        DB::table('employees')->truncate();

        // Load names from files
        $firstNamesPath = database_path('data/first_names.txt');
        $lastNamesPath = database_path('data/last_names.txt');

        if (!file_exists($firstNamesPath) || !file_exists($lastNamesPath)) {
            throw new \Exception("First names or last names file is missing!");
        }

        $firstNames = file($firstNamesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $lastNames = file($lastNamesPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        $firstNames = array_values(array_unique(array_map('trim', $firstNames)));
        $lastNames = array_values(array_unique(array_map('trim', $lastNames)));

        // Generate combinations (Cartesian Product)
        $combinations = [];
        foreach ($firstNames as $first) {
            foreach ($lastNames as $last) {
                $combinations[] = [$first, $last];
            }
        }

        // Shuffle combinations randomly
        shuffle($combinations);

        $totalRecords = 10000;
        if (count($combinations) < $totalRecords) {
            throw new \Exception("Not enough name combinations. We have " . count($combinations) . " but need {$totalRecords}");
        }

        $countries = ['United States', 'United Kingdom', 'Canada', 'India', 'Germany', 'Australia', 'Singapore', 'United Arab Emirates'];
        
        $departments = [
            'Engineering' => ['Software Engineer', 'Senior Software Engineer', 'Tech Lead', 'Engineering Manager', 'DevOps Engineer', 'QA Analyst'],
            'Product' => ['Product Manager', 'Senior Product Manager', 'UX Designer', 'UI Designer'],
            'Sales' => ['Account Executive', 'Business Development Representative', 'Sales Director'],
            'Marketing' => ['Marketing Specialist', 'Growth Marketer', 'Content Writer'],
            'Human Resources' => ['HR Generalist', 'Talent Acquisition Specialist', 'HR Director'],
            'Finance' => ['Accountant', 'Finance Manager', 'Financial Analyst'],
            'Customer Success' => ['Customer Success Manager', 'Support Engineer']
        ];

        $deptKeys = array_keys($departments);

        $records = [];
        $now = \Carbon\Carbon::now();

        $this->command->info("Generating 10,000 unique employee records...");

        for ($i = 0; $i < $totalRecords; $i++) {
            list($first, $last) = $combinations[$i];
            
            // Unique name-based email since combined name is guaranteed unique
            $email = strtolower($first . '.' . $last . '@organization.com');
            
            // Randomly select country, department and job title
            $country = $countries[array_rand($countries)];
            $dept = $deptKeys[array_rand($deptKeys)];
            $titles = $departments[$dept];
            $title = $titles[array_rand($titles)];

            // Realistic salary scaling based on title seniority
            $baseSalary = 50000;
            if (str_contains($title, 'Senior') || str_contains($title, 'Lead')) {
                $baseSalary = 90000;
            } elseif (str_contains($title, 'Manager') || str_contains($title, 'Specialist')) {
                $baseSalary = 80000;
            } elseif (str_contains($title, 'Director')) {
                $baseSalary = 130000;
            }

            // Adjust based on country multiplier for realistic metrics
            $multiplier = 1.0;
            if ($country === 'United States') {
                $multiplier = 1.35;
            } elseif ($country === 'India') {
                $multiplier = 0.65;
            } elseif ($country === 'Germany' || $country === 'Singapore') {
                $multiplier = 1.15;
            } elseif ($country === 'United Arab Emirates') {
                $multiplier = 1.25;
            }

            $salary = round(($baseSalary * $multiplier) + rand(-15000, 25000), 2);
            if ($salary < 30000) $salary = 30000;

            // Hire date: within the last 8 years
            $hireDate = \Carbon\Carbon::now()->subDays(rand(0, 365 * 8))->format('Y-m-d');

            $records[] = [
                'first_name' => $first,
                'last_name' => $last,
                'email' => $email,
                'job_title' => $title,
                'country' => $country,
                'department' => $dept,
                'salary' => $salary,
                'hire_date' => $hireDate,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->command->info("Inserting into database...");
        $startTime = microtime(true);

        DB::transaction(function () use ($records) {
            $chunks = array_chunk($records, 2000);
            foreach ($chunks as $chunk) {
                DB::table('employees')->insert($chunk);
            }
        });

        $endTime = microtime(true);
        $executionTime = round($endTime - $startTime, 2);
        $this->command->info("Database seeded successfully with 10,000 employees in {$executionTime} seconds!");
    }
}
