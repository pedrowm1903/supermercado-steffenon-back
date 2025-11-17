<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    // Checkout — transforma o carrinho em pedido
    public function checkout(Request $request)
    {
        $user = Auth::user();
        $cart = session()->get('cart', []);

        if (empty($cart)) {
            return response()->json(['message' => 'Carrinho vazio!'], 400);
        }

        DB::beginTransaction();

        try {
            $total = collect($cart)->sum(fn($item) => $item['price'] * $item['quantity']);

            $order = Order::create([
                'user_id' => $user->id,
                'total' => $total,
            ]);

            foreach ($cart as $item) {
                $order->products()->attach($item['id'], [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                ]);
            }

            session()->forget('cart'); // limpa o carrinho

            DB::commit();

            return response()->json([
                'message' => 'Pedido finalizado com sucesso!',
                'order' => $order->load('products')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erro ao processar pedido: ' . $e->getMessage()], 500);
        }
    }

    // Listar pedidos do usuário logado
    public function myOrders()
    {
        $user = Auth::user();

        $orders = Order::where('user_id', $user->id)
            ->with('products')
            ->latest()
            ->get();

        return response()->json($orders);
    }
}