<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'items',   // JSON
        'total',
    ];

    protected $casts = [
        'items' => 'array',     // permite trabalhar como array/collection
        'total' => 'decimal:2',
    ];

    /** Um pedido pertence a um usuário */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

        public function products()
    {
        return $this->belongsToMany(Product::class)
                    ->withPivot('quantity', 'unit_price');
    }
}
