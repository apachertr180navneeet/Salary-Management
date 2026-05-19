<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalaryInsightController extends Controller
{
    /**
     * Return JSON analytics or full insights view.
     */
    public function index(Request $request)
    {
        // 1. Global Metrics
        $globalStats = DB::table('employees')
            ->selectRaw('COUNT(*) as total_count, SUM(salary) as total_budget, AVG(salary) as avg_salary, MIN(salary) as min_salary, MAX(salary) as max_salary')
            ->first();

        // 2. Country Insights (Min, Max, Avg Salary, Total Budget, and Counts)
        $countryStats = DB::table('employees')
            ->select('country')
            ->selectRaw('COUNT(*) as count, SUM(salary) as budget, AVG(salary) as avg_salary, MIN(salary) as min_salary, MAX(salary) as max_salary')
            ->groupBy('country')
            ->orderBy('avg_salary', 'desc')
            ->get();

        // 3. Department Budget Allocation
        $departmentStats = DB::table('employees')
            ->select('department')
            ->selectRaw('COUNT(*) as count, SUM(salary) as budget, AVG(salary) as avg_salary')
            ->groupBy('department')
            ->orderBy('budget', 'desc')
            ->get();

        // 4. Salary Distribution Bands (histogram buckets)
        $salaryBands = DB::table('employees')
            ->selectRaw("
                CASE 
                    WHEN salary < 60000 THEN '$30k - $60k'
                    WHEN salary >= 60000 AND salary < 90000 THEN '$60k - $90k'
                    WHEN salary >= 90000 AND salary < 120000 THEN '$90k - $120k'
                    WHEN salary >= 120000 AND salary < 150000 THEN '$120k - $150k'
                    WHEN salary >= 150000 AND salary < 180000 THEN '$150k - $180k'
                    WHEN salary >= 180000 AND salary < 210000 THEN '$180k - $210k'
                    ELSE '$210k+'
                END as band,
                COUNT(*) as count
            ")
            ->groupBy('band')
            ->get();

        $bandOrder = ['$30k - $60k', '$60k - $90k', '$90k - $120k', '$120k - $150k', '$150k - $180k', '$180k - $210k', '$210k+'];
        $salaryBands = collect($salaryBands)->sortBy(function ($item) use ($bandOrder) {
            return array_search($item->band, $bandOrder);
        })->values();

        // 5. Job Titles stats (Top 10 overall)
        $jobTitleStats = DB::table('employees')
            ->select('job_title')
            ->selectRaw('COUNT(*) as count, AVG(salary) as avg_salary')
            ->groupBy('job_title')
            ->orderBy('avg_salary', 'desc')
            ->limit(10)
            ->get();

        // 6. Selected Country Job Title stats (Interactive search)
        $selectedCountry = $request->input('insight_country');
        
        // If no country selected, default to the country with highest average salary
        if (!$selectedCountry && $countryStats->isNotEmpty()) {
            $selectedCountry = $countryStats->first()->country;
        }

        $countryJobStats = [];
        if ($selectedCountry) {
            $countryJobStats = DB::table('employees')
                ->where('country', $selectedCountry)
                ->select('job_title')
                ->selectRaw('COUNT(*) as count, AVG(salary) as avg_salary, MIN(salary) as min_salary, MAX(salary) as max_salary')
                ->groupBy('job_title')
                ->orderBy('avg_salary', 'desc')
                ->get();
        }

        // Return JSON response if requested by AJAX (dynamic charts & tables updates)
        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'globalStats' => $globalStats,
                'countryStats' => $countryStats,
                'departmentStats' => $departmentStats,
                'salaryBands' => $salaryBands,
                'jobTitleStats' => $jobTitleStats,
                'countryJobStats' => $countryJobStats,
                'selectedCountry' => $selectedCountry
            ]);
        }

        // Standard route direct GET access redirects to the unified dashboard
        return redirect()->route('employees.index');
    }
}
