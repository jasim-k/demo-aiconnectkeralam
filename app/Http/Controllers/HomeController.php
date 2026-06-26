<?php

namespace App\Http\Controllers;

use App\Services\ProductService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(): Response
    {
        return Inertia::render('store/home', [
            'hero' => $this->products->firstByModel('iPhone 17 Pro Max'),
            'featured' => $this->products->featured(4),
            'latest' => $this->products->latest(8),
        ]);
    }
}
