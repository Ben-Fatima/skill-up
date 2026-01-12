<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;

class StockController extends Controller
{
    public function index()
    {
        $sku = request('sku');
        $location = request('location');
        $perPage = StockMovement::parsePerPage(request('per_page'));

        $query = StockMovement::query();


        $query->select('product_id', 'location_id')
                    ->join('products', 'products.id', '=', 'stock_movements.product_id')
                    ->join('locations', 'locations.id', '=', 'stock_movements.location_id')
                    ->addSelect('products.sku','locations.code')
                    ->selectRaw('SUM(stock_movements.qty) AS stock_on_hand')
                    ->groupBy('stock_movements.product_id', 'stock_movements.location_id','products.sku','locations.code')
                    ->havingRaw('SUM(stock_movements.qty) != 0')
                    ->orderBy('stock_movements.location_id');

        $query->when($sku, fn($q,$sku) => $q->where('products.sku', '=', $sku));
        $query->when($location, fn($q,$code) => $q->where('locations.code', '=', $code));

        $stock = $query->paginate($perPage);

        return response()->json([
            'message' => 'stock list',
            'data' => $stock
        ], 200);
    }
}
