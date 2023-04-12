<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderProducts;
use App\Models\Warehouses;
use App\Models\Orders;
use App\Models\WarehouseProducts;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class OrdersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Orders = Orders::with('products.product')->paginate(15);
        return jsonResponse(TRUE, '', ['Orders' => $Orders]);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $reqs = $request->toArray();
        try{
            $validator = Validator::make($request->all(), [
                'warehouse_id' => 'required|integer',
                'products' => 'required',
            ]);
            //echo var_dump($request->products);exit;
            if ($validator->fails()) {
                return jsonResponse(FALSE, 'Has Some Errors', ['errors' => $validator->errors()], 422);  
            }
            $products = $request->products;
            $product = $products[0]; // if first product has ok enough
           
            if($this->checkProductStock($products))
            {
                $WarehouseGetForFirst = $this->getFirstAvailableWarehouse($product);
                if($WarehouseGetForFirst['status'])
                {
                    $OrderWareHouse = $WarehouseGetForFirst['result']['Warehouse'];
                    foreach($products as $product)
                    {
                        $warehouseProduct = WarehouseProducts::where('warehouse_id', $OrderWareHouse->id)
                                                              ->where('product_id', $product['product_id'])
                                                              ->first();
                        if($warehouseProduct && $warehouseProduct->stock >= $product['qty'])
                        {
                            $warehouseProduct->stock -= $product['qty'];
                            $warehouseProduct->save();
                        } else {
                            // Transfer from another
                            return jsonResponse(FALSE, 'Insufficient Stock For Product!');
                        }
                    }
                    return jsonResponse(TRUE, 'Order Saved !');
                }
            } else {
                return jsonResponse(FALSE, 'We hasnt this products on stock !');
            }



            

        } catch(Exception $e)
        {
            return jsonResponse(FALSE, $e->getMessage(), []);
        }
    }


    public function getFirstAvailableWarehouse($product, $checkDaily = TRUE)
    {
        // Get first get wirehouse from priority
        $WarehouseRS = Warehouses::orderBy('priority');
        if($checkDaily)
        {
            $WarehouseRS->where('daily_order_limit', '>', function ($query) {
                $query->selectRaw('COALESCE(COUNT(*), 0)')
                    ->from('orders')
                    ->whereRaw('orders.warehouse_id = warehouses.id')
                    ->whereDate('created_at', Carbon::today());
            });
        }

        $WarehouseRS->where(function ($query) use ($product) {

                $query->whereExists(function ($subQuery) use ($product) {
                    $subQuery->select('id')
                            ->from('warehouse_products')
                            ->whereRaw('warehouse_products.warehouse_id = warehouses.id')
                            ->where('warehouse_products.product_id', $product['product_id'])
                            ->where('warehouse_products.stock', '>=', $product['qty']);
                });
        
        });
   
        
        if($WarehouseRS->exists())
        {
            $Warehouse = $WarehouseRS->first();
            return ['status'=>TRUE, 'result'=>['Warehouse'=>$Warehouse]];
        } else {
            return ['status'=>FALSE];
        }
    }
    public function checkProductStock($products)
    {
        $warehouseProductCheck = collect($products)
            ->map(function ($product) {
                return [
                    'product_id' => $product['product_id'],
                    'total_qty' => $product['qty']
                ];
            })
            ->groupBy('product_id')
            ->map(function ($groupedProducts, $product_id) {
                return [
                    'product_id' => $product_id,
                    'total_qty' => $groupedProducts->sum('total_qty')
                ];
            })
            ->toArray();
    
        $warehouseCheck = WarehouseProducts::whereIn('product_id', array_column($warehouseProductCheck, 'product_id'))
            ->selectRaw('product_id, SUM(stock) as total_stock')
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id')
            ->toArray();
    
        $isValid = collect($warehouseProductCheck)
            ->every(function ($product) use ($warehouseCheck) {
                return isset($warehouseCheck[$product['product_id']]) && $warehouseCheck[$product['product_id']]['total_stock'] >= $product['total_qty'];
            });
    
        return $isValid;
    } 


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
