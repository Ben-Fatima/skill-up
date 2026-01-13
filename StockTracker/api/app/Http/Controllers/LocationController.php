<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /**
     * Allowed fields for location operations.
     */
    private const FIELDS = ['code', 'address'];

    /**
     * Display a listing of locations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $locations = Location::searchAndPaginate(request()->query());

        return response()->json([
            'message' => 'List of locations',
            'data' => $locations
        ],200);
    }

    /**
     * Creates new location
     * @return \Illuminate\Http\JsonResponse
     */
    public function store()
    {
        $data = request()->only(self::FIELDS);
        $validated = $this->validateLocation($data);
        $location = Location::create($validated);

        return response()->json([
            'message' => 'Location created successfully',
            'data' => $location
        ], 201);

    }

    /**
     * Get location by ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function show()
    {
        $id = (int)request()->route('id');
        $location = Location::findOrFail($id);

        return response()->json([
            'message' => 'Location retrieved successfully',
            'data' => $location
        ], 200);
    }

    /**
     * Updates a location by ID.
     * @return \Illuminate\Http\JsonResponse
     */
    public function update()
    {
        $id = (int)request()->route('id');
        $data = request()->only(self::FIELDS);

        if(!request()->hasAny(self::FIELDS)){
            return response()->json([
                'message' => 'No data provided for update'
            ], 400);
        }

        $location = Location::findOrFail($id);
        $validated = $this->validateLocation($data, $id, true);
        $location->update($validated);

        return response()->json([
            'message' => 'Location updated successfully',
            'data' => $location
        ], 200);
    }

    /**
     * Deletes a location by ID.
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy()
    {
        $id = (int)request()->route('id');
        $location = Location::findOrFail($id);
        $location->delete();

        return response()->json([
            'message' => 'Location deleted successfully'
        ], 200);

    }

    /**
     * Validates location data.
     * @param array $data
     * @return array
     */
    private function validateLocation(array $data, ?int $id = null, bool $partial = false)
    {
        $presenceRule = $partial ? ['sometimes', 'required'] : ['required'];
        $codeUniqueRule = $id === null
            ? 'unique:locations,code'
            : Rule::unique('locations', 'code')->ignore($id);

        return validator($data, [
            'code' => array_merge($presenceRule, [$codeUniqueRule],['string']),
            'address' => array_merge($presenceRule, ['string','min:3'])
        ])->validate();
    }
}
