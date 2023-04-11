<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Warehouses;
use Illuminate\Support\Facades\Validator;

class WarehousesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Warehouses = Warehouses::paginate(15);
        return jsonResponse(TRUE, '', ['Warehouses' => $Warehouses]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $reqs = $request->toArray();
        try{
            $validator = Validator::make($request->all(), [
                'warehouse_name' => 'required|string',
                'daily_order_limit' => 'required|integer',
                'priority' => 'required|integer',
            ]);
    
            if ($validator->fails()) {
                return jsonResponse(FALSE, 'Has Some Errors', ['errors' => $validator->errors()], 422);  
            }
            $Warehouses = new Warehouses($reqs);
            $Warehouses->save();

            return jsonResponse(TRUE, 'Warehouse Added !');

        } catch(Exception $e)
        {
            return jsonResponse(FALSE, $e->getMessage(), []);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $WarehousesRS = Warehouses::where('id', $id);
            if($WarehousesRS->exists())
            {
                $Warehouse = $WarehousesRS->first();
                return jsonResponse(TRUE, __('Warehouse Found !'), ['Warehouse'=>$Warehouse]);
               
            } else {
                return jsonResponse(TRUE, __('Warehouse Not Found !'), []);
            }
          }catch(Exception $e)
          {
              return jsonResponse(FALSE, $e->getMessage(), []);
          }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try{
            $reqs = $request->toArray();
            $validator = Validator::make($request->all(), [
                'warehouse_name' => 'string',
                'daily_order_limit' => 'integer',
                'priority' => 'integer',
            ]);
    
            if ($validator->fails()) {
                return jsonResponse(FALSE, 'Has Some Errors', ['errors' => $validator->errors()], 422);  
            }
            $WarehousesRS = Warehouses::where('id', $id);
            if($WarehousesRS->exists())
            {
                $Warehouse = $WarehousesRS->first();
                $Warehouse->fill($reqs);
                $Warehouse->save();
                return jsonResponse(TRUE, __('Warehouse Updated !'), []);
               
            }
          }catch(Exception $e)
          {
              return jsonResponse(FALSE, $e->getMessage(), []);
          }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try{
            $WarehousesRS = Warehouses::where('id', $id);
            if($WarehousesRS->exists())
            {
                $Warehouse = $WarehousesRS->first();
    
                $Del = $Warehouse->delete();
                return jsonResponse(TRUE, __('Warehouse Removed !'), []);
               
            }
          }catch(Exception $e)
          {
              return jsonResponse(FALSE, $e->getMessage(), []);
          }
    }
}
