<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class MovementController extends Controller
{
    protected const FIELDS = [
        'location_id',
        'product_id',
        'note',
        'type',
        'qty',
    ];

    /**
     * Lists the movements history
     */
    public function index()
    {
        $productId = request('product_id') ?? null;
        $locationId = request('location_id') ?? null;
        $type = request('type') ?? null;
        $perPage = StockMovement::parsePerPage(request('per_page') ?? null);

        $movements = $this->getMovementHistoryPaginator($productId, $locationId, $type, $perPage);

        return response()->json([
            'message' => 'Movement history',
            'data' => $movements
        ], 200);
    }

    /**
     * Creates new location
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $data = request()->only(self::FIELDS);
        $validated = $this->validateMovement($data);
        $validated['qty'] = $this->normalizeQtyByType($validated['type'], $validated['qty']);

        return DB::transaction(function () use($validated)
        {
            if($validated['type'] === 'OUT')
            {
                $result = $this->hasSufficientStockForOut($validated['product_id'], $validated['location_id'], $validated['qty']);
                if(!$result['ok'])
                {
                    return response()->json([
                    'message' => "Movement Couldn't be created with the given qauntity",
                    'current_stock' => $result['stock'],
                    'requested_out' => abs($validated['qty'])
                    ], 422);
                }
            }

            $movement = StockMovement::create($validated);

            return response()->json([
                'message' => 'Movement added successfully',
                'data' => $movement,
                'current_stock' => StockMovement::stockOnHand($validated['product_id'], $validated['location_id'])
            ], 201);
        });
    }

    /**
     * Validates location data.
     * @param array $data
     * @return array
     */
    private function validateMovement(array $data)
    {
        return validator($data, [
            'product_id' => ['required','integer', 'exists:products,id'],
            'location_id' => ['required','integer', 'exists:locations,id'],
            'type' => ['required', 'string', Rule::in(StockMovement::TYPES)],
            'qty' => ['required', 'integer', 'not_in:0'],
            'note' => ['nullable', 'string']
        ])->validate();
    }

    /**
     * Checks if there is enough stock to take out
     */
    private function hasSufficientStockForOut(int $productId, int $locationId, int $qty): array
    {
        $stock = StockMovement::stockOnHand($productId,$locationId);

        return ['ok' => $stock + $qty >= 0, 'stock' => $stock];
    }

    /**
     *
     */
    private function normalizeQtyByType(string $type, int $qty) : int
    {
        switch ($type) {
            case 'IN':
                $qty = abs($qty);
                break;
            case 'OUT':
                $qty = abs($qty) * -1;
                break;
        }

        return $qty;
    }

    private function getMovementHistoryPaginator($productId, $locationId, $type, $perPage)
    {
        $query = StockMovement::query();

        $query->when($productId, fn($q, $id) => $q->where('product_id', $id));
        $query->when($locationId, fn($q, $id) => $q->where('location_id', $id));
        $query->when($type, function($q, $id) {
            if(in_array($id, StockMovement::TYPES, true)){
               $q->where('type', $id);
            }
        });

        $query->orderBy('created_at','desc');
        $paginator = $query->paginate($perPage);

        foreach($paginator->items() as $i)
        {
            if($i['type'] === 'IN' || $i['type'] === 'OUT'){
                 $i['qty'] = abs($i['qty']);
            }
        }

        return $paginator;
    }
}
