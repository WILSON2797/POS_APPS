<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'type',
        'quantity',
        'old_stock',
        'new_stock',
        'reference_type',
        'reference_id',
        'note',
    ];

    /**
     * Relasi ke Produk terkait.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke User/Kasir/Admin yang memicu tindakan.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi polymorphic fleksibel untuk pemicu mutasi.
     */
    public function reference()
    {
        return $this->morphTo();
    }
}
