<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the employees, with sorting and filters.
     */
    public function index(Request $request)
    {
        $query = Employee::query();

        // Search by name or email
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtering
        if ($request->filled('country')) {
            $query->where('country', $request->input('country'));
        }
        if ($request->filled('job_title')) {
            $query->where('job_title', $request->input('job_title'));
        }
        if ($request->filled('department')) {
            $query->where('department', $request->input('department'));
        }

        // Sorting
        $sortField = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['id', 'first_name', 'last_name', 'country', 'job_title', 'department', 'salary', 'hire_date'];
        
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder === 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy('id', 'desc');
        }

        $employees = $query->paginate(12)->withQueryString();

        // If AJAX request, return partial HTML for table and pagination
        if ($request->ajax()) {
            return response()->json([
                'html' => view('partials.employee_table', compact('employees'))->render(),
                'pagination' => view('partials.pagination', compact('employees'))->render()
            ]);
        }

        // Fetch distinct filter options from database for HR selection
        $countries = Employee::select('country')->distinct()->orderBy('country')->pluck('country');
        $departments = Employee::select('department')->distinct()->orderBy('department')->pluck('department');
        $jobTitles = Employee::select('job_title')->distinct()->orderBy('job_title')->pluck('job_title');

        // Initial salary insights so dashboard is loaded instantly with graphs
        $globalStats = DB::table('employees')
            ->selectRaw('COUNT(*) as total_count, SUM(salary) as total_budget, AVG(salary) as avg_salary, MIN(salary) as min_salary, MAX(salary) as max_salary')
            ->first();

        $countryStats = DB::table('employees')
            ->select('country')
            ->selectRaw('COUNT(*) as count, SUM(salary) as budget, AVG(salary) as avg_salary, MIN(salary) as min_salary, MAX(salary) as max_salary')
            ->groupBy('country')
            ->orderBy('avg_salary', 'desc')
            ->get();

        $departmentStats = DB::table('employees')
            ->select('department')
            ->selectRaw('COUNT(*) as count, SUM(salary) as budget, AVG(salary) as avg_salary')
            ->groupBy('department')
            ->orderBy('budget', 'desc')
            ->get();

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

        $selectedCountry = $countries->first() ?? '';
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

        return view('dashboard', compact(
            'employees', 'countries', 'departments', 'jobTitles',
            'globalStats', 'countryStats', 'departmentStats', 'salaryBands', 'countryJobStats', 'selectedCountry'
        ));
    }

    /**
     * Store a newly created employee record in the database.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|max:150|unique:employees,email',
            'job_title' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
        ]);

        $employee = Employee::create($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee created successfully.',
                'employee' => $employee
            ], 201);
        }

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified employee details as JSON for edit forms.
     */
    public function show(Employee $employee)
    {
        return response()->json($employee);
    }

    /**
     * Update the specified employee record in the database.
     */
    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('employees')->ignore($employee->id),
            ],
            'job_title' => 'required|string|max:100',
            'country' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'salary' => 'required|numeric|min:0',
            'hire_date' => 'required|date',
        ]);

        $employee->update($validated);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee updated successfully.',
                'employee' => $employee
            ]);
        }

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified employee record from the database.
     */
    public function destroy(Request $request, Employee $employee)
    {
        $employee->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Employee deleted successfully.'
            ]);
        }

        return redirect()->route('employees.index')->with('success', 'Employee deleted successfully.');
    }
}
