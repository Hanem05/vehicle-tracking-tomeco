<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View { return view('users.index', ['users' => User::orderBy('name')->paginate(20)]); }
    public function create(): View { return view('users.form', ['user' => new User]); }
    public function edit(User $user): View { return view('users.form', compact('user')); }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'enforcer'])],
        ]);
        $data['is_active'] = true;
        $user = User::create($data);
        Audit::record('user.created', $user, $user->role);
        return redirect()->route('users.index')->with('success', 'User created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:12', 'confirmed'],
            'role' => ['required', Rule::in(['admin', 'enforcer'])],
            'is_active' => ['required', 'boolean'],
        ]);
        if ($user->is($request->user()) && ($data['role'] !== 'admin' || ! $data['is_active'])) {
            return back()->withErrors(['role' => 'You cannot remove your own admin access.']);
        }
        if ($user->role === 'admin' && $user->is_active && ($data['role'] !== 'admin' || ! $data['is_active']) && User::where('role', 'admin')->where('is_active', true)->count() <= 1) {
            return back()->withErrors(['role' => 'At least one active admin is required.']);
        }
        if (blank($data['password'] ?? null)) unset($data['password']);
        $user->update($data);
        Audit::record('user.updated', $user, $user->role.'; active='.($user->is_active ? 'yes' : 'no'));
        return redirect()->route('users.index')->with('success', 'User updated.');
    }
}
