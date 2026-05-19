# PayScaleHub - Project Flow Diagrams

This document contains high-fidelity Mermaid flowcharts outlining the sequential data, routing, and user interface flows across all core PayScaleHub operations.

---

## 1. High-Performance Cartesian Seeding Flow

This flowchart illustrates the initialization and database insertion process of the `EmployeeSeeder` class, completing 10,000 unique records in under a second.

```mermaid
graph TD
    Start([1. Start Seeder]) --> ReadFiles[2. Read first_names.txt & last_names.txt]
    ReadFiles --> UniquePool[3. Deduplicate Name Arrays]
    UniquePool --> Cartesian[4. Generate Cartesian Names Pool <br/> 200 x 200 = 40,000 Names]
    Cartesian --> Shuffle[5. Shuffle Name Combinations]
    
    subgraph Iterative Loop [Chunk Generation]
        GetNames[6. Pick Next Combined Name] --> FormatEmail[7. Create unique email <br/> name@organization.com]
        FormatEmail --> ApplyMultipliers[8. Calculate base salary based on country <br/> and role seniority multipliers]
        ApplyMultipliers --> BuildRow[9. Compile Array Row]
        BuildRow --> CheckChunk{10. Chunk size reached 2,000?}
        CheckChunk -- No --> GetNames
    end
    
    CheckChunk -- Yes --> Transaction[11. Open DB Transaction]
    Transaction --> BulkInsert[12. Execute Raw Bulk Insert <br/> DB::table]
    BulkInsert --> Commit[13. Commit Transaction]
    Commit --> Done{14. All 10,000 inserted?}
    Done -- No --> IterativeLoop
    Done -- Yes --> End([15. End Seeder])
```

---

## 2. Asynchronous Search, Filtering & Pagination Flow

This flowchart shows the execution pipeline when the HR Manager types in the search bar or changes any filter criteria, resulting in a zero-reload dynamic search.

```mermaid
graph TD
    UI[HR types in Search or modifies filter dropdown] --> Debounce[300ms Debouncer <br/> debouncedFilter]
    Debounce --> Fetch[Async native Fetch GET request sent]
    Fetch --> Route[Laravel routes/web.php routes to index]
    
    subgraph Controller Query [EmployeeController@index]
        ParseParams[Parse Search, Country, Dept, and Job parameters] --> CheckSearch{Is search active?}
        CheckSearch -- Yes --> QuerySearch[Apply SQL search clauses]
        CheckSearch -- No --> CheckFilters{Are filters active?}
        QuerySearch --> CheckFilters
        CheckFilters -- Yes --> QueryFilters[Apply SQL filter clauses]
        CheckFilters -- No --> Sort[Apply sort order & page index]
        QueryFilters --> Sort
        Sort --> DB[Execute SQL Query]
    end
    
    DB -.->|Covering B-Tree Index Scan| Index[idx_country_job_title_salary]
    DB --> Output[Paginated Employee Collection]
    
    subgraph Server-Side Rendering
        Output --> RenderTable[Render Blade partial: partials/employee_table]
        Output --> RenderPagination[Render Blade partial: partials/pagination]
    end
    
    RenderTable --> JSON[Return JSON response with HTML blocks]
    RenderPagination --> JSON
    JSON --> Client[JavaScript receives JSON payload]
    Client --> Inject[Swap tbody.innerHTML & paginationWrapper.innerHTML]
    Inject --> ResetOpacity[Reset container opacity to 1.0]
```

---

## 3. Interactive AJAX CRUD Flow

This flowchart describes the record creation/update process, dynamic server validation handling, and background dashboard re-aggregation.

```mermaid
graph TD
    Start[HR clicks Add or Edit Employee] --> Modal[Glassmorphic Modal slides up & form resets]
    Modal --> Input[HR fills fields & clicks Save]
    Input --> Request[JavaScript Fetch POST/PUT request sent]
    Request --> Validator[Laravel Controller FormRequest Validation]
    
    Validator --> CheckValid{Is data valid?}
    
    CheckValid -- No --> FormatErrors[Laravel generates 422 JSON Validation Object]
    FormatErrors --> ClientErrors[JavaScript catches error payload]
    ClientErrors --> DrawHighlights[Highlight failed input fields in red <br/> Print error text under input labels]
    
    CheckValid -- Yes --> SaveDB[Eloquent Employee::create or Employee::update]
    SaveDB --> SuccessJSON[Return 200/201 JSON Success response]
    SuccessJSON --> ClientSuccess[JavaScript clears modal form & closes overlay]
    ClientSuccess --> Toast[Display floating Success Toast notification]
    
    subgraph Background Re-aggregation [Silent Dashboard Updates]
        ClientSuccess --> FetchInsights[Fetch GET request to /salary-insights API]
        FetchInsights --> DBInsights[Run B-Tree Aggregations]
        DBInsights --> ReturnInsights[Return fresh aggregations JSON]
        ReturnInsights --> RefreshUI[1. Update stats cards: Staff Count, Total Budget, Average Salary]
        ReturnInsights --> RedrawCharts[2. Trigger ApexCharts .updateSeries for Salary Bands, Regions, & Depts]
        ReturnInsights --> RefreshTable[3. Refresh current page directory datatable]
    end
```

---

## 4. Regional Job Market Explorer Flow

This flowchart outlines the live analytical aggregation requested when changing target markets in the regional explorer widget.

```mermaid
graph TD
    Dropdown[HR selects Country in market explorer dropdown] --> Fetch[Async Fetch GET /salary-insights?insight_country]
    Fetch --> Route[Laravel routes/web.php routes to SalaryInsightController@index]
    
    subgraph Query Aggregation [SalaryInsightController]
        FilterCountry[Filter employees by selected country] --> GroupJob[GROUP BY job_title]
        GroupJob --> CalcMetrics[Aggregate: COUNT, AVG, MIN, MAX salaries]
        CalcMetrics --> Execute[Execute Query]
    end
    
    Execute -.->|Index Covered Scan| Index[idx_country_job_title_salary]
    Execute --> Collection[Output job aggregates collection]
    Collection --> ReturnJSON[Return JSON response payload]
    ReturnJSON --> Redraw[Redraw Explorer table rows with Min/Max/Avg spreads]
```
