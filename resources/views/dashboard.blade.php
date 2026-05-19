@extends('layouts.app')

@section('title', 'PayScaleHub - Enterprise Salary Analytics')

@section('content')
    <!-- Stats ribbon -->
    <div class="stats-ribbon">
        <div class="stat-card">
            <div class="stat-details">
                <span class="stat-title">Total Staff</span>
                <span class="stat-value" id="stat-total-count">{{ number_format($globalStats->total_count ?? 0) }}</span>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-details">
                <span class="stat-title">Total Payroll</span>
                <span class="stat-value" id="stat-total-budget">${{ number_format(($globalStats->total_budget ?? 0) / 1000000, 2) }}M</span>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-details">
                <span class="stat-title">Average Salary</span>
                <span class="stat-value" id="stat-avg-salary">${{ number_format($globalStats->avg_salary ?? 0, 2) }}</span>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-chart-line"></i></div>
        </div>
        <div class="stat-card">
            <div class="stat-details">
                <span class="stat-title">Active Markets</span>
                <span class="stat-value">{{ $countries->count() }} Regions</span>
            </div>
            <div class="stat-icon"><i class="fa-solid fa-earth-americas"></i></div>
        </div>
    </div>

    <!-- Tabbing system -->
    <div class="tabs-header">
        <button class="tab-btn active" onclick="switchTab(event, 'directory-tab')">
            <i class="fa-solid fa-list-check"></i> Employee Directory
        </button>
        <button class="tab-btn" onclick="switchTab(event, 'insights-tab')">
            <i class="fa-solid fa-chart-pie"></i> Salary Insights Dashboard
        </button>
    </div>

    <!-- TAB 1: EMPLOYEE CRUD DIRECTORY -->
    <div id="directory-tab" class="tab-content active">
        <div class="panel-card">
            <div class="panel-header">
                <h2 class="panel-title"><i class="fa-solid fa-users"></i> Employee Database</h2>
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fa-solid fa-user-plus"></i> Add New Employee
                </button>
            </div>

            <!-- Search, Filters, and Sorting Controls -->
            <div class="directory-controls">
                <div class="filter-group">
                    <div class="input-search-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" id="filter-search" class="input-control" placeholder="Search by name, email..." oninput="debouncedFilter()">
                    </div>

                    <select id="filter-department" class="input-control" style="max-width: 180px;" onchange="filterData()">
                        <option value="">All Departments</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept }}">{{ $dept }}</option>
                        @endforeach
                    </select>

                    <select id="filter-country" class="input-control" style="max-width: 180px;" onchange="filterData()">
                        <option value="">All Countries</option>
                        @foreach($countries as $country)
                            <option value="{{ $country }}">{{ $country }}</option>
                        @endforeach
                    </select>

                    <select id="filter-job-title" class="input-control" style="max-width: 180px;" onchange="filterData()">
                        <option value="">All Job Titles</option>
                        @foreach($jobTitles as $title)
                            <option value="{{ $title }}">{{ $title }}</option>
                        @endforeach
                    </select>

                    <button class="btn btn-secondary btn-sm" onclick="resetFilters()" title="Reset Filters">
                        <i class="fa-solid fa-rotate-left"></i> Reset
                    </button>
                </div>
            </div>

            <!-- Main Datatable -->
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th class="sortable active" onclick="changeSort(event, 'first_name')">Employee <i class="fa-solid fa-sort-down"></i></th>
                            <th class="sortable" onclick="changeSort(event, 'job_title')">Job Title <i class="fa-solid fa-sort"></i></th>
                            <th class="sortable" onclick="changeSort(event, 'department')">Department <i class="fa-solid fa-sort"></i></th>
                            <th class="sortable" onclick="changeSort(event, 'country')">Country <i class="fa-solid fa-sort"></i></th>
                            <th class="sortable" onclick="changeSort(event, 'salary')">Salary <i class="fa-solid fa-sort"></i></th>
                            <th class="sortable" onclick="changeSort(event, 'hire_date')">Hire Date <i class="fa-solid fa-sort"></i></th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employee-table-body">
                        @include('partials.employee_table')
                    </tbody>
                </table>
            </div>

            <!-- Dynamic Pagination Block -->
            <div id="pagination-wrapper">
                @include('partials.pagination')
            </div>
        </div>
    </div>

    <!-- TAB 2: SALARY INSIGHTS & ANALYTICS -->
    <div id="insights-tab" class="tab-content">
        <!-- Visual Charts Section -->
        <div class="insights-grid">
            <!-- Chart 1: Salary Distribution -->
            <div class="chart-panel">
                <h3 class="panel-title" style="margin-bottom: 1.5rem;"><i class="fa-solid fa-chart-bar"></i> Salary Distribution Bands</h3>
                <div id="salary-bands-chart" style="min-height: 350px;"></div>
            </div>

            <!-- Chart 2: Average Salary by Country -->
            <div class="chart-panel">
                <h3 class="panel-title" style="margin-bottom: 1.5rem;"><i class="fa-solid fa-chart-area"></i> Average Salary per Region</h3>
                <div id="country-salary-chart" style="min-height: 350px;"></div>
            </div>

            <!-- Chart 3: Department Budget Allocations -->
            <div class="chart-panel" style="grid-column: span 2;">
                <h3 class="panel-title" style="margin-bottom: 1.5rem;"><i class="fa-solid fa-chart-pie"></i> Department Budget & Staff Share</h3>
                <div id="department-budget-chart" style="min-height: 350px;"></div>
            </div>
        </div>

        <!-- Dynamic Country-Job Explorer -->
        <div class="explorer-card">
            <div class="explorer-header">
                <h3 class="panel-title"><i class="fa-solid fa-circle-nodes"></i> Regional Job Market Explorer</h3>
                <div class="explorer-filter" style="display: flex; align-items: center; gap: 0.75rem;">
                    <label class="form-label" style="margin: 0; white-space: nowrap;">Country Focus:</label>
                    <select id="explorer-country-select" class="input-control" style="width: 220px;" onchange="fetchCountryInsights()">
                        @foreach($countries as $country)
                            <option value="{{ $country }}" {{ $country === $selectedCountry ? 'selected' : '' }}>{{ $country }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Job Title</th>
                            <th>Staff Count</th>
                            <th>Average Salary</th>
                            <th>Minimum Salary</th>
                            <th>Maximum Salary</th>
                            <th>Salary Spread (Min - Max)</th>
                        </tr>
                    </thead>
                    <tbody id="explorer-table-body">
                        @forelse($countryJobStats as $jobStat)
                            <tr>
                                <td class="emp-name">{{ $jobStat->job_title }}</td>
                                <td><span class="badge-dept">{{ $jobStat->count }} staff</span></td>
                                <td class="salary-cell">${{ number_format($jobStat->avg_salary, 2) }}</td>
                                <td>${{ number_format($jobStat->min_salary, 2) }}</td>
                                <td>${{ number_format($jobStat->max_salary, 2) }}</td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                        ${{ number_format($jobStat->min_salary) }} &rarr; ${{ number_format($jobStat->max_salary) }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                                    No analytical metrics available for this country.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <!-- CREATE & EDIT EMPLOYEE GLASSMORPHIC MODAL -->
    <div id="employee-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event)">
        <div class="modal-card">
            <div class="modal-header">
                <h3 class="modal-title" id="modal-title">Add New Employee</h3>
                <button class="modal-close" onclick="closeEmployeeModal()">&times;</button>
            </div>
            <form id="employee-form" onsubmit="submitEmployeeForm(event)">
                <input type="hidden" id="emp-id" name="id">
                <div class="modal-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="emp-first-name">First Name</label>
                            <input type="text" id="emp-first-name" name="first_name" class="input-control" required placeholder="e.g. John">
                            <span class="form-error" id="error-first_name"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emp-last-name">Last Name</label>
                            <input type="text" id="emp-last-name" name="last_name" class="input-control" required placeholder="e.g. Smith">
                            <span class="form-error" id="error-last_name"></span>
                        </div>
                        <div class="form-group form-group-full">
                            <label class="form-label" for="emp-email">Corporate Email</label>
                            <input type="email" id="emp-email" name="email" class="input-control" required placeholder="john.smith@organization.com">
                            <span class="form-error" id="error-email"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emp-department">Department</label>
                            <select id="emp-department" name="department" class="input-control" required>
                                <option value="">Select Department</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Product">Product</option>
                                <option value="Sales">Sales</option>
                                <option value="Marketing">Marketing</option>
                                <option value="Human Resources">Human Resources</option>
                                <option value="Finance">Finance</option>
                                <option value="Customer Success">Customer Success</option>
                            </select>
                            <span class="form-error" id="error-department"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emp-job-title">Job Title</label>
                            <input type="text" id="emp-job-title" name="job_title" class="input-control" required placeholder="e.g. Software Engineer">
                            <span class="form-error" id="error-job_title"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emp-country">Country</label>
                            <select id="emp-country" name="country" class="input-control" required>
                                <option value="">Select Country</option>
                                <option value="United States">United States</option>
                                <option value="United Kingdom">United Kingdom</option>
                                <option value="Canada">Canada</option>
                                <option value="India">India</option>
                                <option value="Germany">Germany</option>
                                <option value="Australia">Australia</option>
                                <option value="Singapore">Singapore</option>
                                <option value="United Arab Emirates">United Arab Emirates</option>
                            </select>
                            <span class="form-error" id="error-country"></span>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emp-salary">Annual Salary (USD)</label>
                            <input type="number" id="emp-salary" name="salary" class="input-control" required min="0" step="0.01" placeholder="e.g. 85000">
                            <span class="form-error" id="error-salary"></span>
                        </div>
                        <div class="form-group form-group-full">
                            <label class="form-label" for="emp-hire-date">Date of Hire</label>
                            <input type="date" id="emp-hire-date" name="hire_date" class="input-control" required>
                            <span class="form-error" id="error-hire_date"></span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEmployeeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="btn-save-employee">Save Employee</button>
                </div>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="delete-modal" class="modal-overlay" onclick="closeModalOnOuterClick(event)">
        <div class="modal-card" style="max-width: 450px;">
            <div class="modal-header">
                <h3 class="modal-title">Delete Employee Record</h3>
                <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body" style="text-align: center;">
                <i class="fa-solid fa-circle-exclamation" style="font-size: 3rem; color: var(--accent-red); margin-bottom: 1rem; display: block;"></i>
                <p>Are you sure you want to delete the record of <strong id="delete-name" class="salary-cell"></strong>?</p>
                <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">This action cannot be undone and will immediately remove the employee from salary rosters.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="btn-confirm-delete" onclick="executeDelete()">Delete Record</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Global variables for active queries
        let searchTimer;
        let activeSortField = 'id';
        let activeSortOrder = 'desc';
        let activePage = 1;
        let deleteCandidateId = null;

        // Switching top tabs
        function switchTab(evt, tabId) {
            const tabs = document.querySelectorAll('.tab-content');
            const btns = document.querySelectorAll('.tab-btn');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            btns.forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            evt.currentTarget.classList.add('active');
            
            // Resize apex charts inside tabs on switch to render them perfectly
            if (tabId === 'insights-tab') {
                window.dispatchEvent(new Event('resize'));
            }
        }

        /* ------------------ CRUD SYSTEM MODALS ------------------ */
        function openAddModal() {
            document.getElementById('modal-title').innerText = "Add New Employee";
            document.getElementById('employee-form').reset();
            document.getElementById('emp-id').value = "";
            clearFormErrors();
            document.getElementById('employee-modal').classList.add('open');
        }

        function openEditModal(id) {
            clearFormErrors();
            fetch(`/employees/${id}`)
                .then(res => res.json())
                .then(emp => {
                    document.getElementById('modal-title').innerText = "Edit Employee Profile";
                    document.getElementById('emp-id').value = emp.id;
                    document.getElementById('emp-first-name').value = emp.first_name;
                    document.getElementById('emp-last-name').value = emp.last_name;
                    document.getElementById('emp-email').value = emp.email;
                    document.getElementById('emp-department').value = emp.department;
                    document.getElementById('emp-job-title').value = emp.job_title;
                    document.getElementById('emp-country').value = emp.country;
                    document.getElementById('emp-salary').value = emp.salary;
                    
                    // Format Carbon date for html input (YYYY-MM-DD)
                    const date = new Date(emp.hire_date);
                    const formattedDate = date.toISOString().split('T')[0];
                    document.getElementById('emp-hire-date').value = formattedDate;

                    document.getElementById('employee-modal').classList.add('open');
                })
                .catch(err => {
                    showToast('Failed to load employee details.', 'error');
                });
        }

        function closeEmployeeModal() {
            document.getElementById('employee-modal').classList.remove('open');
        }

        function closeModalOnOuterClick(event) {
            if (event.target === event.currentTarget) {
                event.target.classList.remove('open');
            }
        }

        function confirmDelete(id, name) {
            deleteCandidateId = id;
            document.getElementById('delete-name').innerText = name;
            document.getElementById('delete-modal').classList.add('open');
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('open');
            deleteCandidateId = null;
        }

        function clearFormErrors() {
            document.querySelectorAll('.form-error').forEach(span => span.innerText = "");
            document.querySelectorAll('.input-control').forEach(input => input.style.borderColor = "");
        }

        /* ------------------ FORM SUBMISSIONS ------------------ */
        function submitEmployeeForm(event) {
            event.preventDefault();
            clearFormErrors();
            
            const form = document.getElementById('employee-form');
            const formData = new FormData(form);
            const empId = document.getElementById('emp-id').value;
            
            const url = empId ? `/employees/${empId}` : '/employees';
            
            // For Laravel route mapping updates
            if (empId) {
                formData.append('_method', 'PUT');
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: formData
            })
            .then(async res => {
                const data = await res.json();
                if (res.ok) {
                    closeEmployeeModal();
                    showToast(data.message || 'Action executed successfully.', 'success');
                    
                    // Instant silent refresh of datatable and global insights
                    refreshAppDirectory();
                } else {
                    if (data.errors) {
                        Object.keys(data.errors).forEach(key => {
                            const errorSpan = document.getElementById(`error-${key}`);
                            const inputField = document.getElementById(`emp-${key.replace('_', '-')}`);
                            if (errorSpan) {
                                errorSpan.innerText = data.errors[key][0];
                            }
                            if (inputField) {
                                inputField.style.borderColor = 'var(--accent-red)';
                            }
                        });
                    } else {
                        showToast(data.message || 'Operation failed.', 'error');
                    }
                }
            })
            .catch(err => {
                showToast('An unexpected server error occurred.', 'error');
            });
        }

        function executeDelete() {
            if (!deleteCandidateId) return;

            fetch(`/employees/${deleteCandidateId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': window.csrfToken,
                    'Accept': 'application/json'
                },
                body: new URLSearchParams({
                    '_method': 'DELETE'
                })
            })
            .then(async res => {
                const data = await res.json();
                if (res.ok) {
                    closeDeleteModal();
                    showToast(data.message || 'Record successfully deleted.', 'success');
                    refreshAppDirectory();
                } else {
                    showToast(data.message || 'Failed to delete record.', 'error');
                }
            })
            .catch(err => {
                showToast('Server connection failed.', 'error');
            });
        }

        /* ------------------ AJAX DIRECTORY SEARCH / FILTER ------------------ */
        function debouncedFilter() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(filterData, 300);
        }

        function filterData() {
            activePage = 1; // Reset to page 1 on active search or filters change
            fetchDirectoryResults();
        }

        function goToPage(page) {
            activePage = page;
            fetchDirectoryResults();
        }

        function changeSort(event, field) {
            const th = event.currentTarget;
            const isAsc = th.classList.contains('active') && th.querySelector('.fa-sort-down');
            
            // Clear active sorts in UI header
            document.querySelectorAll('.custom-table th').forEach(hdr => {
                hdr.classList.remove('active');
                const sortIcon = hdr.querySelector('i');
                if (sortIcon) {
                    sortIcon.className = 'fa-solid fa-sort';
                }
            });

            activeSortField = field;
            activeSortOrder = isAsc ? 'asc' : 'desc';

            th.classList.add('active');
            th.querySelector('i').className = isAsc ? 'fa-solid fa-sort-up' : 'fa-solid fa-sort-down';

            fetchDirectoryResults();
        }

        function resetFilters() {
            document.getElementById('filter-search').value = "";
            document.getElementById('filter-department').value = "";
            document.getElementById('filter-country').value = "";
            document.getElementById('filter-job-title').value = "";
            
            activePage = 1;
            activeSortField = 'id';
            activeSortOrder = 'desc';

            // Reset UI Header Sort icons
            document.querySelectorAll('.custom-table th').forEach(hdr => {
                hdr.classList.remove('active');
                const icon = hdr.querySelector('i');
                if (icon) {
                    icon.className = 'fa-solid fa-sort';
                }
            });
            const firstHeader = document.querySelector('.custom-table th:first-child');
            firstHeader.classList.add('active');
            firstHeader.querySelector('i').className = 'fa-solid fa-sort-down';

            fetchDirectoryResults();
        }

        function fetchDirectoryResults() {
            const search = document.getElementById('filter-search').value;
            const dept = document.getElementById('filter-department').value;
            const country = document.getElementById('filter-country').value;
            const jobTitle = document.getElementById('filter-job-title').value;

            const params = new URLSearchParams({
                search: search,
                department: dept,
                country: country,
                job_title: jobTitle,
                sort_by: activeSortField,
                sort_order: activeSortOrder,
                page: activePage
            });

            // Smooth opacity shift to show loading state
            const tableBody = document.getElementById('employee-table-body');
            tableBody.style.opacity = '0.5';

            fetch(`/?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                tableBody.innerHTML = data.html;
                document.getElementById('pagination-wrapper').innerHTML = data.pagination;
                tableBody.style.opacity = '1';
            })
            .catch(err => {
                tableBody.style.opacity = '1';
                showToast('Connection timed out, failed to search.', 'error');
            });
        }

        // Silent refresh of directory and stats panels on CRUD actions
        function refreshAppDirectory() {
            fetchDirectoryResults();
            
            // Re-aggregate and update stats ribbon cards & charts!
            fetch('/salary-insights')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Update stats ribbon values
                    document.getElementById('stat-total-count').innerText = Number(data.globalStats.total_count).toLocaleString();
                    document.getElementById('stat-total-budget').innerText = '$' + (Number(data.globalStats.total_budget) / 1000000).toFixed(2) + 'M';
                    document.getElementById('stat-avg-salary').innerText = '$' + Number(data.globalStats.avg_salary).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    
                    // Update Charts datasets dynamically!
                    updateInsightsCharts(data);
                    
                    // Refresh regional explorer if matching
                    refreshExplorerTable(data.countryJobStats);
                }
            });
        }

        /* ------------------ REGIONAL EXPLORER AJAX ------------------ */
        function fetchCountryInsights() {
            const country = document.getElementById('explorer-country-select').value;
            
            const tbody = document.getElementById('explorer-table-body');
            tbody.style.opacity = '0.5';

            fetch(`/salary-insights?insight_country=${encodeURIComponent(country)}`)
            .then(res => res.json())
            .then(data => {
                refreshExplorerTable(data.countryJobStats);
                tbody.style.opacity = '1';
            })
            .catch(err => {
                tbody.style.opacity = '1';
                showToast('Failed to load explorer data.', 'error');
            });
        }

        function refreshExplorerTable(jobStats) {
            const tbody = document.getElementById('explorer-table-body');
            if (!jobStats || jobStats.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            No analytical metrics available for this country.
                        </td>
                    </tr>
                `;
                return;
            }

            let html = '';
            jobStats.forEach(job => {
                html += `
                    <tr>
                        <td class="emp-name">${job.job_title}</td>
                        <td><span class="badge-dept">${job.count} staff</span></td>
                        <td class="salary-cell">$${Number(job.avg_salary).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td>$${Number(job.min_salary).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td>$${Number(job.max_salary).toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                        <td>
                            <div style="font-size: 0.8rem; color: var(--text-secondary);">
                                $${Number(job.min_salary).toLocaleString(undefined, {maximumFractionDigits: 0})} &rarr; $${Number(job.max_salary).toLocaleString(undefined, {maximumFractionDigits: 0})}
                            </div>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        /* ------------------ APEXCHARTS VISUAL SYSTEMS ------------------ */
        let bandsChart, countryChart, deptChart;

        function initInsightsCharts() {
            // Curated chart styling configurations
            const fontStyle = {
                fontFamily: 'Outfit, sans-serif',
                colors: '#9CA3AF'
            };

            // Chart 1: Salary Distribution Bands
            const bandsData = @json($salaryBands);
            const bandsCategories = bandsData.map(d => d.band);
            const bandsSeries = bandsData.map(d => d.count);

            bandsChart = new ApexCharts(document.querySelector("#salary-bands-chart"), {
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: { show: false },
                    background: 'transparent'
                },
                theme: { mode: 'dark' },
                colors: ['#00F2FE'],
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        columnWidth: '55%',
                        dataLabels: { position: 'top' }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function (val) { return val + " staff"; },
                    offsetY: -20,
                    style: { fontSize: '12px', colors: ["#F3F4F6"] }
                },
                series: [{
                    name: 'Employee Count',
                    data: bandsSeries
                }],
                xaxis: {
                    categories: bandsCategories,
                    labels: { style: fontStyle }
                },
                yaxis: {
                    labels: { style: fontStyle },
                    title: { text: 'Staff Count', style: { color: '#9CA3AF' } }
                },
                grid: { borderColor: 'rgba(255, 255, 255, 0.08)' }
            });
            bandsChart.render();

            // Chart 2: Average Salary per Region
            const countryData = @json($countryStats);
            const countryCategories = countryData.map(d => d.country);
            const countryAverages = countryData.map(d => Math.round(d.avg_salary));

            countryChart = new ApexCharts(document.querySelector("#country-salary-chart"), {
                chart: {
                    type: 'area',
                    height: 350,
                    toolbar: { show: false },
                    background: 'transparent'
                },
                theme: { mode: 'dark' },
                colors: ['#4F46E5'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.5,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 3 },
                series: [{
                    name: 'Average Annual Salary',
                    data: countryAverages
                }],
                xaxis: {
                    categories: countryCategories,
                    labels: { style: fontStyle }
                },
                yaxis: {
                    labels: {
                        style: fontStyle,
                        formatter: function(val) { return "$" + val.toLocaleString(); }
                    },
                    title: { text: 'Average Salary (USD)', style: { color: '#9CA3AF' } }
                },
                grid: { borderColor: 'rgba(255, 255, 255, 0.08)' }
            });
            countryChart.render();

            // Chart 3: Department Budget & Staff Share
            const deptData = @json($departmentStats);
            const deptLabels = deptData.map(d => d.department);
            const deptBudgets = deptData.map(d => Math.round(d.budget));

            deptChart = new ApexCharts(document.querySelector("#department-budget-chart"), {
                chart: {
                    type: 'donut',
                    height: 350,
                    background: 'transparent'
                },
                theme: { mode: 'dark' },
                colors: ['#4F46E5', '#00F2FE', '#10B981', '#F59E0B', '#EF4444', '#EC4899', '#8B5CF6'],
                stroke: { show: false },
                series: deptBudgets,
                labels: deptLabels,
                legend: {
                    position: 'right',
                    fontFamily: 'Outfit',
                    fontSize: '14px',
                    labels: { colors: '#F3F4F6' }
                },
                dataLabels: { enabled: true },
                tooltip: {
                    y: {
                        formatter: function(val) { return "$" + val.toLocaleString(); }
                    }
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                name: { show: true, fontSize: '16px', color: '#9CA3AF' },
                                value: {
                                    show: true,
                                    fontSize: '22px',
                                    fontWeight: '700',
                                    color: '#F3F4F6',
                                    formatter: function(val) { return "$" + Number(val).toLocaleString(); }
                                },
                                total: {
                                    show: true,
                                    label: 'Total Budget',
                                    color: '#9CA3AF',
                                    formatter: function(w) {
                                        const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                        return "$" + sum.toLocaleString();
                                    }
                                }
                            }
                        }
                    }
                }
            });
            deptChart.render();
        }

        // Dynamically update charts on new employee data inserts without full page refresh
        function updateInsightsCharts(data) {
            // Update Salary Bands Chart
            const bandsCategories = data.salaryBands.map(d => d.band);
            const bandsSeries = data.salaryBands.map(d => d.count);
            bandsChart.updateSeries([{
                data: bandsSeries
            }]);
            bandsChart.updateOptions({
                xaxis: { categories: bandsCategories }
            });

            // Update Country Salary Chart
            const countryCategories = data.countryStats.map(d => d.country);
            const countryAverages = data.countryStats.map(d => Math.round(d.avg_salary));
            countryChart.updateSeries([{
                data: countryAverages
            }]);
            countryChart.updateOptions({
                xaxis: { categories: countryCategories }
            });

            // Update Department Budgets Chart
            const deptBudgets = data.departmentStats.map(d => Math.round(d.budget));
            const deptLabels = data.departmentStats.map(d => d.department);
            deptChart.updateSeries(deptBudgets);
        }

        // Initialize components on load
        window.addEventListener('DOMContentLoaded', () => {
            initInsightsCharts();
        });
    </script>
@endsection
