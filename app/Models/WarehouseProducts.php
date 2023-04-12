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
        'warehouse_id',
        'stock'
    ];
    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];
    public function product()
    {
        return $this->belongsTo('App\Models\Products', 'product_id');
    }
}
