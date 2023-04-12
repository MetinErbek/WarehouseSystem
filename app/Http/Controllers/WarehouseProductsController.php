<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WareHouseProducts;
use Illuminate\Support\Facades\Validator;


class WarehouseProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
    }



    /**
     * Update the specified resource in storage.
     */
    public function setWarehouseStock(Request $request)
    {
        try
        {
            $reqs = $request->toArray();
            $validator = Validator::make($request->all(), [
                'product_id'    => 'required|integer',
                'warehouse_id'  => 'required|integer',
                'stock'         => 'required'
            ]);
    
            if ($validator->fails()) {
                return jsonResponse(FALSE, 'Has Some Errors', ['errors' => $validator->errors()], 422);  
            }

            $StockRS = WareHouseProducts::where('product_id', $reqs['product_id'])->where('warehouse_id',$reqs['warehouse_id'] );
            if($StockRS->exists())
            {
                $Stock = $StockRS->first();
            } else {
                $Stock = new WareHouseProducts($reqs);
            }
            $Stock->fill(['stock'=>$reqs['stock']]);
            $Stock->save();
            return jsonResponse(TRUE, 'Warehouse stock updated !');
        } catch(Exception $e)
        {
            return jsonResponse(FALSE, $e->getMessage(), []);
        }
    }


}
