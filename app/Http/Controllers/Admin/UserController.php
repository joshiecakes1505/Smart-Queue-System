<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Inertia\Inertia;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $users = User::with('role')->orderBy('name')->get();
        return Inertia::render('Admin/Users/Index', ['users' => $users]);
    }

    public function create()
    {
        $roles = \App\Models\Role::pluck('name', 'id');
        return Inertia::render('Admin/Users/Create', ['roles' => $roles]);
    }

    public function store(StoreUserRequest $request)
    {
        User::create(array_merge(
            $request->validated(),
            ['password' => Hash::make($request->password)]
        ));
        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        $roles = \App\Models\Role::pluck('name', 'id');
        return Inertia::render('Admin/Users/Edit', ['user' => $user, 'roles' => $roles]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $user->update($request->validated());
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
