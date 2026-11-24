<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Maintains the medicine catalogue, sale prices, and pharmacy stock levels.
 */
class MedicineController extends Controller
{
    /**
     * Builds the medicine listing with the applicable filters, summary data, and pagination.
     */
    public function index(Request $request): View
    {
        $medicines = Medicine::query()
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.$request->string('q')->toString().'%';
                $query->where(fn ($search) => $search->where('name', 'like', $term)->orWhere('sku', 'like', $term)->orWhere('strength', 'like', $term));
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('medicines.index', compact('medicines'));
    }

    /**
     * Validates and persists a new medicine record, then returns the appropriate response.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules());
        Medicine::create($data);

        return back()->with('success', 'Medicine added to the catalogue.');
    }

    /**
     * Validates and applies changes to the medicine record, preserving its related business rules.
     */
    public function update(Request $request, Medicine $medicine): RedirectResponse
    {
        $data = $request->validate($this->rules($medicine));
        if ((int) $data['stock_quantity'] < $medicine->reserved_quantity) {
            throw ValidationException::withMessages(['stock_quantity' => 'Stock on hand cannot be set below the quantity reserved for pending prescriptions.']);
        }

        $medicine->update($data);

        return back()->with('success', 'Medicine details and stock updated.');
    }

    /**
     * Returns the validation rules that define acceptable input for this request.
     */
    private function rules(?Medicine $medicine = null): array
    {
        return [
            'sku' => ['required', 'string', 'max:50', Rule::unique('medicines', 'sku')->ignore($medicine?->id)],
            'name' => ['required', 'string', 'max:150'],
            'strength' => ['nullable', 'string', 'max:80'],
            'dosage_form' => ['nullable', 'string', 'max:80'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'stock_quantity' => ['required', 'integer', 'min:0', 'max:100000000'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:100000000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
