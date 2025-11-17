<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function add(Request $request)
    {
        try {
            Log::info('Add to cart request:', $request->all());

            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1|max:999'
            ]);

            $user = auth()->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
            }

            $product = Product::where('id', $validated['product_id'])
                              ->whereNull('deleted_at')
                              ->first();

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Produto não encontrado'], 404);
            }

            // Verificar se já existe no carrinho
            $cartItem = Cart::where('user_id', $user->id)
                            ->where('product_id', $product->id)
                            ->first();

            if ($cartItem) {
                $cartItem->quantity += $validated['quantity'];
                $cartItem->save();
            } else {
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
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Cart error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao adicionar produto'], 500);
        }
    }

    public function get()
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
            }

            return response()->json([
                'success' => true,
                'cart' => $this->getCartData($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Get cart error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao buscar carrinho'], 500);
        }
    }

    public function remove(Request $request)
    {
        try {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:products,id'
            ]);

            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
            }

            Cart::where('user_id', $user->id)
                ->where('product_id', $validated['product_id'])
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Produto removido do carrinho',
                'cart' => $this->getCartData($user)
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Remove cart error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao remover produto'], 500);
        }
    }

    public function clear()
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
            }

            Cart::where('user_id', $user->id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Carrinho limpo',
                'cart' => $this->getCartData($user)
            ]);

        } catch (\Exception $e) {
            Log::error('Clear cart error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao limpar carrinho'], 500);
        }
    }

    private function getCartData($user)
    {
        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        $total = $cartItems->sum(fn($item) => $item->product->price * $item->quantity);

        return [
            'items' => $cartItems->map(function ($item) {
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'name' => $item->product->name,
                    'price' => $item->product->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->product->price * $item->quantity,
                ];
            }),
            'total' => $total,
            'count' => $cartItems->sum('quantity')
        ];
    }
}
