<?php

namespace App\Http\Controllers;

use App\Models\ViolationType;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ViolationTypeController extends Controller
{
    public function index(): View { return view('types.index', ['types' => ViolationType::orderBy('name')->get()]); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:violation_types,name'],
            'default_penalty' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);
        $type = ViolationType::create($data + ['is_active' => true]);
        Audit::record('type.created', $type, $type->name);
        return back()->with('success', 'Violation type added.');
    }

    public function update(Request $request, ViolationType $type): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('violation_types', 'name')->ignore($type->id)],
            'default_penalty' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'is_active' => ['required', 'boolean'],
        ]);
        $type->update($data);
        Audit::record('type.updated', $type, $type->name);
        return back()->with('success', 'Violation type updated.');
    }
}
