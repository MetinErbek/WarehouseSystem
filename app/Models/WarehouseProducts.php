<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseProducts extends Model
{
    use HasFactory;
    protected $table = 'warehouse_products';
    protected $fillable = [
        'product_id',
        'stock'
    ];
}
