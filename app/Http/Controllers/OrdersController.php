<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\OrderProducts;
use App\Models\Warehouses;
use App\Models\Orders;
use App\Models\WarehouseProducts;
use App\Models\ProductTransfers;
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
                    $Order = new Orders(['warehouse_id'=> $OrderWareHouse->id]);
                    $Order->save();
                    $ALLOrderProducts = [];
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
                            if(!$warehouseProduct)
                            {
                                $need = $product['qty'];
                            } else {
                                $warehouseProduct->stock = 0;
                                $warehouseProduct->save();
                                $need = $product['qty']-$warehouseProduct->stock;
                            }

                            while($need > 0)
                            {
                               $WHRS = $this->getFirstAvailableWarehouse($product, FALSE);
                               if($WHRS['status'])
                               {
                                    $TransferWH = $WHRS['result']['Warehouse'];
                                    $transferWarehouseProduct = WarehouseProducts::where('warehouse_id', $TransferWH->id)
                                    ->where('product_id', $product['product_id'])
                                    ->first();

                                    if($transferWarehouseProduct && $transferWarehouseProduct->stock >= $product['qty'])
                                    {
                                        $transferWarehouseProduct->stock -= $need ;
                                        $transferWarehouseProduct->save();
                                        
                                        $transfered = $need;
                                        $need = 0;
                                    } else {

                                        $transfered = $transferWarehouseProduct->stock;
                                        $transferWarehouseProduct->stock = 0 ;
                                        $transferWarehouseProduct->save();
                                        $need -= $transferWarehouseProduct->stock;
                                    }
                                    ProductTransfers::create([
                                        'product_id' => $product['product_id'],
                                        'qty'=> $transfered,
                                        'from_warehouse_id' =>$TransferWH->id,
                                        'to_warehouse_id' =>$OrderWareHouse->id,

                                    ]);

                               }
                            }

                            
                        } // not in wirehouse end
                        
                        $ALLOrderProducts[] = new OrderProducts([
                            'qty'           => $product['qty'],
                            'product_id'    => $product['product_id'],
                            'order_id'      => $Order->id
                        ]);
                    }
                    $Order->products()->saveMany($ALLOrderProducts);
                    
                    return jsonResponse(TRUE, 'Order Saved !');
                } else {
                    return jsonResponse(FALSE, 'We hasnt available warehouse for this order !');
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
            $WarehouseRS = $WarehouseRS->where('daily_order_limit', '>', function ($query) {
                $query->selectRaw('COALESCE(COUNT(*), 0)')
                    ->from('orders')
                    ->whereRaw('orders.warehouse_id = warehouses.id')
                    ->whereDate('created_at', Carbon::today());
            });
        }

        $WarehouseRS = $WarehouseRS->where(function ($query) use ($product) {

                $query->whereExists(function ($subQuery) use ($product) {
                    $subQuery->select('id')
                            ->from('warehouse_products')
                            ->whereRaw('warehouse_products.warehouse_id = warehouses.id')
                            ->where('warehouse_products.product_id', $product['product_id'])
                            ->where('warehouse_products.stock', '>', '0');
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
