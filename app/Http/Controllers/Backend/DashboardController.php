<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Blog;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $counts = [
            'blogs' => Blog::count(),
        ];

        return view('backend.dashboard.index', compact('counts'));
    }
}
