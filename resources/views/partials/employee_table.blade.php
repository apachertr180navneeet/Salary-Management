@forelse($employees as $employee)
    <tr id="employee-row-{{ $employee->id }}">
        <td>
            <div class="employee-cell">
                <span class="emp-name">{{ $employee->full_name }}</span>
                <span class="emp-email">{{ $employee->email }}</span>
            </div>
        </td>
        <td>{{ $employee->job_title }}</td>
        <td>
            <span class="badge-dept">{{ $employee->department }}</span>
        </td>
        <td>
            <div class="country-cell">
                <i class="fa-solid fa-location-dot" style="color: var(--accent-cyan); opacity: 0.8;"></i>
                <span>{{ $employee->country }}</span>
            </div>
        </td>
        <td class="salary-cell">${{ number_format($employee->salary, 2) }}</td>
        <td>{{ $employee->hire_date ? $employee->hire_date->format('M d, Y') : '-' }}</td>
        <td class="actions-cell">
            <button class="btn btn-secondary btn-sm" onclick="openEditModal({{ $employee->id }})" title="Edit Employee">
                <i class="fa-solid fa-pen-to-square"></i>
            </button>
            <button class="btn btn-danger btn-sm" onclick="confirmDelete({{ $employee->id }}, '{{ addslashes($employee->full_name) }}')" title="Delete Employee">
                <i class="fa-solid fa-trash"></i>
            </button>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" style="text-align: center; padding: 3rem; color: var(--text-secondary);">
            <i class="fa-solid fa-users-slash" style="font-size: 2.5rem; margin-bottom: 1rem; display: block; opacity: 0.4; background: var(--accent-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
            No employees found matching the current search parameters.
        </td>
    </tr>
@endforelse
