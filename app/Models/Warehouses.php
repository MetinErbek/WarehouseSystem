<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Softdeletes;

class Warehouses extends Model
{
    use HasFactory, Softdeletes;
    protected $table = 'warehouses';
    protected $fillable = [
        'warehouse_name',
        'daily_order_limit',        
        'priority'
    ];
    protected $hidden = [
        'updated_at',
        'deleted_at',
    ];
    public function stocks()
    {
        return $this->hasMany('App\Models\WarehouseProducts', 'warehouse_id');
    }
}
