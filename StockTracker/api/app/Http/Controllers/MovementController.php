<?php

namespace App\Http\Controllers;

use App\Models\StockMovement;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;


class MovementController extends Controller
{
    /**
     * Allowed fields for movement operations.
     */
    private const FIELDS = [
                            'product_id',
                            'location_id',
                            'type',
                            'qty',
                            'note'
                        ];

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
     *
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
}
