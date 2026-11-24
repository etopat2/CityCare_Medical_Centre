<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Manages the clinic department catalogue used by doctor profiles and service organization.
 */
class DepartmentController extends Controller
{
    /**
     * Builds the department listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $departments = Department::query()
            ->withCount('doctors')
            ->when($request->string('q')->isNotEmpty(), fn ($query) => $query->where('name', 'like', '%'.$request->string('q')->toString().'%'))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('departments.index', compact('departments'));
    }

    /**
     * Returns the form or initial state needed to create a department record.
     */
    public function create(): View
    {
        return view('departments.create');
    }

    /**
     * Validates and persists a new department record, then returns the appropriate response.
     */
    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated() + ['is_active' => $request->boolean('is_active', true)]);

        return redirect()->route('departments.index')->with('success', 'Department created.');
    }

    /**
     * Loads the department record and edit data required by its form.
     */
    public function edit(Department $department): View
    {
        return view('departments.edit', compact('department'));
    }

    /**
     * Validates and applies changes to the department record, preserving its related business rules.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated() + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('departments.index')->with('success', 'Department updated.');
    }

    /**
     * Applies the deletion or archival rules for the department record and reports the result.
     */
    public function destroy(Department $department): RedirectResponse
    {
        abort_unless(request()->user()->can('departments.manage'), 403);

        if ($department->doctors()->exists()) {
            return back()->with('error', 'Move or deactivate the department’s doctors before removing it.');
        }

        $department->delete();

        return redirect()->route('departments.index')->with('success', 'Department removed.');
    }
}
