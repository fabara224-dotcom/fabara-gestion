<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:9'],
        ]);
        Category::create($data);

        return back()->with('success', 'Catégorie « '.$data['name'].' » créée.');
    }

    public function destroy(Category $category)
    {
        $category->products()->update(['category_id' => null]);
        $category->delete();

        return back()->with('success', 'Catégorie supprimée.');
    }
}
