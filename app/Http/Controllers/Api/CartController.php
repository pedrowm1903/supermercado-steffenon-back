<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function add(Request $request)
    {
        try {
            // Log para debug
            Log::info('Add to cart request:', $request->all());

            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }

            $product = Product::find($validated['product_id']);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produto não encontrado'
                ], 404);
            }

            // Verifica se o produto já está no carrinho
            $cartItem = Cart::where('user_id', $user->id)
                           ->where('product_id', $product->id)
                           ->first();

            if ($cartItem) {
                // Atualiza quantidade
                $cartItem->quantity += $validated['quantity'];
                $cartItem->save();
            } else {
                // Cria novo item
                $cartItem = Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'quantity' => $validated['quantity']
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Produto adicionado ao carrinho',
                'cart' => $this->getCartData($user)
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('Cart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar produto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function get()
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado'
                ], 401);
            }
            
            return response()->json([
                'success' => true,
                'cart' => $this->getCartData($user)
            ], 200);
            
        } catch (\Exception $e) {
            Log::error('Get cart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar carrinho: ' . $e->getMessage()
            ], 500);
        }
    }

    public function remove(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id'
            ]);

            $user = auth()->user();
            
            Cart::where('user_id', $user->id)
                ->where('product_id', $validated['product_id'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produto removido do carrinho',
                'cart' => $this->getCartData($user)
            ], 200);

        } catch (\Exception $e) {
            Log::error('Remove from cart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover produto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function clear()
    {
        try {
            $user = auth()->user();
            Cart::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Carrinho limpo'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Clear cart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao limpar carrinho: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getCartData($user)
    {
        $cartItems = Cart::where('user_id', $user->id)
                        ->with('product')
                        ->get();

        $total = $cartItems->sum(function($item) {
            return $item->product->price * $item->quantity;
        });

        return [
            'items' => $cartItems,
            'total' => $total,
            'count' => $cartItems->sum('quantity')
        ];
    }
}