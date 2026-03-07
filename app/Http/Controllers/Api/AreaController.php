<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Area;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $areas = Area::query()
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('des', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'areas' => $areas->items(),
                'pagination' => [
                    'current_page' => $areas->currentPage(),
                    'last_page' => $areas->lastPage(),
                    'per_page' => $areas->perPage(),
                    'total' => $areas->total(),
                    'next_page_url' => $areas->nextPageUrl(),
                    'prev_page_url' => $areas->previousPageUrl(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'des' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $area = Area::create([
            'nama' => $request->nama,
            'des' => $request->des,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Area created successfully',
            'data' => $area
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $area = Area::find($id);

        if (!$area) {
            return response()->json([
                'success' => false,
                'message' => 'Area not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Area details retrieved successfully',
            'data' => $area
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $area = Area::find($id);

        if (!$area) {
            return response()->json([
                'success' => false,
                'message' => 'Area not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'des' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $area->update([
            'nama' => $request->nama,
            'des' => $request->des,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Area updated successfully',
            'data' => $area
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $area = Area::find($id);

        if (!$area) {
            return response()->json([
                'success' => false,
                'message' => 'Area not found'
            ], 404);
        }

        $area->delete();

        return response()->json([
            'success' => true,
            'message' => 'Area deleted successfully'
        ]);
    }
}
