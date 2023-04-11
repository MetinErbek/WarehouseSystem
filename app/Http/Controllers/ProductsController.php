<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Products;
use Illuminate\Support\Facades\Validator;


class ProductsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $Products = Products::paginate(15);
        return jsonResponse(TRUE, '', ['Products' => $Products]);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $reqs = $request->toArray();
        try{
            $validator = Validator::make($request->all(), [
                'product_name' => 'required|string',
                'sku' => 'required|string|unique:products',
            ]);
    
            if ($validator->fails()) {
                return jsonResponse(FALSE, 'Has Some Errors', ['errors' => $validator->errors()], 422);  
            }
            $Product = new Products($reqs);
            $Product->save();

            return jsonResponse(TRUE, 'Product Added !');

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
            $ProductRS = Products::where('id', $id);
            if($ProductRS->exists())
            {
                $Product = $ProductRS->first();
                return jsonResponse(TRUE, __('Product Found !'), ['Product'=>$Product]);
               
            } else {
                return jsonResponse(TRUE, __('Product Not Found !'), []);
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
                'product_name' => 'required|string',
                'sku' => 'string|unique:products',
            ]);
    
            if ($validator->fails()) {
                return jsonResponse(FALSE, 'Has Some Errors', ['errors' => $validator->errors()], 422);  
            }
            $ProductRS = Products::where('id', $id);
            if($ProductRS->exists())
            {
                $Product = $ProductRS->first();
                $Product->fill($reqs);
                $Product->save();
                return jsonResponse(TRUE, __('Product Updated !'), []);
               
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
            $ProductRS = Products::where('id', $id);
            if($ProductRS->exists())
            {
                $Product = $ProductRS->first();
    
                $Del = $Product->delete();
                return jsonResponse(TRUE, __('Product Removed !'), []);
               
            }
          }catch(Exception $e)
          {
              return jsonResponse(FALSE, $e->getMessage(), []);
          }
    }
}
