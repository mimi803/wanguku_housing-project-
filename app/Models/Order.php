<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public $timestamps = false;
    protected $fillable = ['product_id', 'buyer_id', 'quantity', 'total_amount', 'phone', 'checkout_request_id', 'mpesa_receipt', 'status', 'delivery_notes', 'payment_method', 'payment_status', 'created_at', 'updated_at'];

    public function product() { return $this->belongsTo(Product::class); }
    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
}
