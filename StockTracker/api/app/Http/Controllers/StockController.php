<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Builder;

class StockController extends Controller
{
    /**
     * Return a paginated list of stock on hand with optional filters.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $sku = request('sku');
        $location = request('location');
        $perPage = StockMovement::parsePerPage(request('per_page'));

        $query = StockMovement::query();

        $query = $this->stockOnHandQuery($query);

        $query->when($sku, fn($q,$sku) => $q->where('products.sku', '=', $sku));
        $query->when($location, fn($q,$code) => $q->where('locations.code', '=', $code));

        $stock = $query->paginate($perPage);

        return response()->json([
            'message' => 'Stock list',
            'data' => $stock
        ], 200);
    }

    /**
     * Return paginated stock on hand for a specific product.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductStock()
    {
        $productId = request()->route('id');
        $query = StockMovement::query();
        $perPage = StockMovement::parsePerPage(request('per_page'));

        $query = $this->stockOnHandQuery($query)->where('stock_movements.product_id','=',$productId);

        return response()->json([
            'message' => 'Stock for product',
            'data' => $query->paginate($perPage)
        ], 200);
    }

    /**
     * Return paginated stock on hand for a specific location.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLocationStock()
    {
        $locationId = request()->route('id');
        $query = StockMovement::query();
        $perPage = StockMovement::parsePerPage(request('per_page'));

        $query = $this->stockOnHandQuery($query)->where('stock_movements.location_id','=',$locationId);

        return response()->json([
            'message' => 'Stock for location',
            'data' => $query->paginate($perPage)
        ], 200);
    }

    /**
     * Return a low stock report with the minimum threshold.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLowStockReport()
    {
        $query = StockMovement::query();
        $perPage = StockMovement::parsePerPage(request('per_page'));

        $stock = $this->stockOnHandQuery($query, true)
                    ->havingRaw('SUM(stock_movements.qty) < products.min_stock');

        return response()->json([
            'message' => 'Low stock report',
            'data' => $stock->paginate($perPage),
            'threshold' => StockMovement::MIN_ALLOWED_QTY
        ], 200);

    }

    /**
     * Build the base stock on hand query.
     *
     * @param Builder $query
     * @param bool $isReport
     * @return Builder
     */
    private function stockOnHandQuery(Builder $query, bool $isReport = false) : Builder
    {
        if($isReport) {
            return $query->select('stock_movements.location_id', 'stock_movements.product_id')
                        ->join('products', 'products.id', '=', 'stock_movements.product_id')
                        ->join('locations', 'locations.id', '=', 'stock_movements.location_id')
                        ->addSelect('products.sku','locations.code')
                        ->selectRaw('SUM(stock_movements.qty) AS stock_on_hand')
                        ->groupBy('stock_movements.product_id', 'stock_movements.location_id','products.sku','locations.code')
                        ->orderBy('stock_movements.location_id');
        }
        return $query->select('stock_movements.location_id', 'stock_movements.product_id')
                    ->join('products', 'products.id', '=', 'stock_movements.product_id')
                    ->join('locations', 'locations.id', '=', 'stock_movements.location_id')
                    ->addSelect('products.sku','locations.code')
                    ->selectRaw('SUM(stock_movements.qty) AS stock_on_hand')
                    ->groupBy('stock_movements.product_id', 'stock_movements.location_id','products.sku','locations.code')
                    ->havingRaw('SUM(stock_movements.qty) != 0')
                    ->orderBy('stock_movements.location_id');
    }
}
