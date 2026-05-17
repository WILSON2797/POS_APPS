<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_no',
        'user_id',
        'customer_id',
        'subtotal',
        'discount',
        'tax',
        'grand_total',
        'amount_paid',
        'change',
        'payment_method',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function details()
    {
        return $this->hasMany(TransactionDetail::class);
    }

    // Helper to generate Invoice Number automatically
    public static function generateInvoiceNo(): string
    {
        $prefix = 'INV/' . date('Ymd') . '/';
        $lastTransaction = self::where('invoice_no', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = intval(substr($lastTransaction->invoice_no, -5));
            $nextNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '00001';
        }

        return $prefix . $nextNumber;
    }
}
