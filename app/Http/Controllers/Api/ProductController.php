<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
    }

    public function show($id)
    {
        return Product::findOrFail($id);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image_url' => 'nullable|url',
            'code_erp' => 'nullable|string|max:255',
        ]);

        $produto = Product::create($data);

        return response()->json($produto, 201);
    }

    public function update(Request $request, $id)
    {
        $produto = Product::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image_url' => 'nullable|url',
            'code_erp' => 'nullable|string|max:255',
        ]);

        $produto->update($data);

        return response()->json($produto);
    }

    public function destroy($id)
    {
    Product::findOrFail($id)->delete();

        return response()->json(['message' => 'Produto excluído com sucesso']);
    }
}
