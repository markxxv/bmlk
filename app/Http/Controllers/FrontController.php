<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class FrontController extends Controller
{
    public function home(): View
    {
        $coffrets = Product::query()
            ->where('category_id', 7)
            ->where('active', true)
            ->with('media')
            ->orderBy('id')
            ->get();

        return view('home', compact('coffrets'));
    }
}
