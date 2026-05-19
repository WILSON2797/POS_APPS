<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsReceipt extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receipt_no',
        'supplier_id',
        'user_id',
        'receipt_date',
        'reference_no',
        'total_cost',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'receipt_date' => 'date',
            'total_cost' => 'decimal:2',
        ];
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(GoodsReceiptDetail::class);
    }
}
