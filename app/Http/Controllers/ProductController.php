<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function destroy(Product $product)
    {
        // Hapus file gambar jika ada
        if ($product->file_url && Storage::disk('public')->exists($product->file_url)) {
            Storage::disk('public')->delete($product->file_url);
        }

        $product->delete();

        return redirect()->back()->with('success', 'Produk berhasil dihapus.');
    }

    public function getProducts()
    {
        $search = request('search');
        return \App\Models\Product::when($search, function ($query) use ($search) {
            $query->where('product_name', 'like', "%{$search}%");
        })->get();
    }
}
