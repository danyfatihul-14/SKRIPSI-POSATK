<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    /**
     * Dashboard publik (sebelum login)
     * Tampilkan featured products
     */
    public function index()
    {
        $featuredProducts = Product::with('category')
            ->inRandomOrder()
            ->limit(8)
            ->get();

        $categories = DB::table('categories')
            ->select('categories.*')
            ->selectSub(function ($query) {
                $query->from('products')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('products.category_id', 'categories.category_id');
            }, 'products_count')
            ->get();

        $stats = [
            'total_products' => Product::count(),
            'total_categories' => DB::table('categories')->count(),
        ];

        return view('public.dashboard', [
            'featuredProducts' => $featuredProducts,
            'categories' => $categories,
        ]);
    }

    /**
     * Halaman catalog - semua produk dengan filter/search
     */
    public function catalog(Request $request)
    {
        $query = Product::with('category');

        // Filter by category
        if ($request->has('category') && $request->category != '') {
            $query->where('category_id', $request->category);
        }

        // Search by product name
        if ($request->has('search') && $request->search != '') {
            $query->where('product_name', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'latest');
        if ($sortBy === 'price_asc') {
            $query->orderBy('selling_price', 'asc');
        } elseif ($sortBy === 'price_desc') {
            $query->orderBy('selling_price', 'desc');
        } elseif ($sortBy === 'rating') {
            $query->orderBy('rating', 'desc');
        } else {
            $query->latest('created_at');
        }

        $products = $query->paginate(12);
        $categories = DB::table('categories')->get();

        return view('public.catalog', [
            'products' => $products,
            'categories' => $categories,
            'searchQuery' => $request->get('search', ''),
            'selectedCategory' => $request->get('category', ''),
            'sortBy' => $sortBy,
        ]);
    }

    /**
     * Detail halaman produk (opsional)
     */
    public function show(Product $product)
    {
        $relatedProducts = Product::where('category_id', $product->category_id)
            ->where('product_id', '!=', $product->product_id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('public.product-detail', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
