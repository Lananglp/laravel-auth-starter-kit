<?php

namespace App\Http\Controllers\Account;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\User;
use App\Models\Role;
use App\Models\SystemConfig;

class AccountController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::with('role')->latest();

        if ($request->has('search') && $request->search !== null) {
            $query->where('name', 'like', '%' . $request->search . '%')
                ->orWhere('email', 'like', '%' . $request->search . '%');
        }

        if ($request->has('role_id') && $request->role_id !== null) {
            $query->where('role_id', $request->role_id);
        }

        $users = $query->get()->makeHidden(['password', 'remember_token', 'email_verified_at']);
        $roles = Role::all();

        return Inertia::render('account/account', [
            'users' => $users,
            'roles' => $roles,
            'filters' => [
                'search' => $request->search,
                'role_id' => $request->role_id,
            ]
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $isDefault = $request->boolean('isDefaultPassword');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:' . User::class,
        ];

        if (!$isDefault) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        $validated = $request->validate($rules);

        // Ambil password default jika isDefaultPassword true
        $password = $isDefault
            ? SystemConfig::firstOrFail()->default_user_password
            : $request->password;

        // Buat username dari nama
        $username = $this->generateUsername($request->name);

        // Cari role 'user'
        $role = Role::where('slug', 'user')->firstOrFail();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'username' => $username,
            'password' => Hash::make($password),
            'provider' => 'none',
            'role_id' => $role->id,
        ]);

        return back()->with('status', 'Account created successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'resetToDefaultPassword' => 'sometimes|boolean',
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if ($request->boolean('resetToDefaultPassword')) {
            $defaultPassword = SystemConfig::firstOrFail()->default_user_password;
            $updateData['password'] = Hash::make($defaultPassword);
        }

        $user->update($updateData);

        return back()->with('status', 'Account updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $user->delete();

        return back()->with('status', 'Account deleted successfully.');
    }

    public function setRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->role_id = $validated['role_id'];
        $user->save();

        return back()->with('success', 'Successfully updated user role.');
    }

    private function generateUsername(string $name): string
    {
        // Ubah ke lowercase
        $lower = strtolower($name);

        // Hapus semua karakter kecuali huruf, angka, dan underscore
        $clean = preg_replace('/[^a-z0-9_]/', '', $lower);

        // Potong ke max 30 karakter jika terlalu panjang
        $clean = substr($clean, 0, 30);

        $original = $clean;
        $i = 1;

        // Pastikan username unik
        while (User::where('username', $clean)->exists()) {
            $suffix = (string)$i++;
            $cutLength = 30 - strlen($suffix); // jaga agar tetap maksimal 30 karakter
            $clean = substr($original, 0, $cutLength) . $suffix;
        }

        return $clean;
    }
}
