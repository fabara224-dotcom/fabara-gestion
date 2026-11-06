<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /** Photo prise avec le téléphone, envoyée après la synchro de l'article */
    public function uploadImage(Request $request, string $id)
    {
        $request->validate(['image' => ['required', 'image', 'max:5120']]);
        $product = Product::findOrFail($id); // filtré par entreprise

        if ($product->image_path) Storage::disk('public')->delete($product->image_path);
        $product->image_path = $request->file('image')->store('products/'.$product->tenant_id, 'public');
        $product->save();

        return response()->json(['image_url' => $product->image_url]);
    }
}
