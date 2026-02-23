<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function daily()
    {
        // TODO: fetch report data
        return Inertia::render('Admin/Reports/Daily', [
            'metrics' => [],
        ]);
    }
}
