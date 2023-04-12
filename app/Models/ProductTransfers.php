<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductTransfers extends Model
{
    use HasFactory;
    protected $table = 'product_transfers';
    protected $fillable = [
        'product_id',
        'from_warehouse_id',
        'to_warehouse_id',
        'qty'
    ];
}
