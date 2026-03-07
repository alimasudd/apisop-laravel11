<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ruang;
use Illuminate\Support\Facades\Validator;

class RuangController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $ruangs = Ruang::query()
            ->with('area')
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('des', 'like', "%{$search}%")
                    ->orWhereHas('area', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'ruangs' => $ruangs->items(),
                'pagination' => [
                    'current_page' => $ruangs->currentPage(),
                    'last_page' => $ruangs->lastPage(),
                    'per_page' => $ruangs->perPage(),
                    'total' => $ruangs->total(),
                    'next_page_url' => $ruangs->nextPageUrl(),
                    'prev_page_url' => $ruangs->previousPageUrl(),
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
            'area_id' => 'required|integer|exists:m_area,id',
            'nama' => 'required|string|max:255',
            'des' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $ruang = Ruang::create([
            'area_id' => $request->area_id,
            'nama' => $request->nama,
            'des' => $request->des,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ruang created successfully',
            'data' => $ruang->load('area')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $ruang = Ruang::with('area')->find($id);

        if (!$ruang) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ruang details retrieved successfully',
            'data' => $ruang
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'area_id' => 'required|integer|exists:m_area,id',
            'nama' => 'required|string|max:255',
            'des' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $ruang->update([
            'area_id' => $request->area_id,
            'nama' => $request->nama,
            'des' => $request->des,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ruang updated successfully',
            'data' => $ruang->load('area')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ruang = Ruang::find($id);

        if (!$ruang) {
            return response()->json([
                'success' => false,
                'message' => 'Ruang not found'
            ], 404);
        }

        $ruang->delete();

        return response()->json([
            'success' => true,
            'message' => 'Ruang deleted successfully'
        ]);
    }
}
