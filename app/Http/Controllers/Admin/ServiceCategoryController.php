<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreServiceCategoryRequest;
use App\Http\Requests\UpdateServiceCategoryRequest;
use App\Models\ServiceCategory;
use Inertia\Inertia;

class ServiceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin');
    }

    public function index()
    {
        $categories = ServiceCategory::orderBy('name')->get();
        return Inertia::render('Admin/ServiceCategories/Index', ['categories' => $categories]);
    }

    public function create()
    {
        return Inertia::render('Admin/ServiceCategories/Create');
    }

    public function store(StoreServiceCategoryRequest $request)
    {
        ServiceCategory::create($request->validated());
        return redirect()->route('admin.service-categories.index')->with('success', 'Category created.');
    }

    public function edit(ServiceCategory $serviceCategory)
    {
        return Inertia::render('Admin/ServiceCategories/Edit', ['category' => $serviceCategory]);
    }

    public function update(UpdateServiceCategoryRequest $request, ServiceCategory $serviceCategory)
    {
        $serviceCategory->update($request->validated());
        return redirect()->route('admin.service-categories.index')->with('success', 'Category updated.');
    }

    public function destroy(ServiceCategory $serviceCategory)
    {
        $serviceCategory->delete();
        return redirect()->route('admin.service-categories.index')->with('success', 'Category deleted.');
    }
}
