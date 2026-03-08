<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SopPelaksana;
use Illuminate\Support\Facades\Validator;

class SopPelaksanaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $pelaksanaans = SopPelaksana::query()
            ->with(['sop.kategori', 'langkah', 'user', 'area', 'ruang'])
            ->when($search, function ($query, $search) {
                $query->whereHas('sop', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('area', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ruang', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'pelaksanaans' => $pelaksanaans->items(),
                'pagination' => [
                    'current_page' => $pelaksanaans->currentPage(),
                    'last_page' => $pelaksanaans->lastPage(),
                    'per_page' => $pelaksanaans->perPage(),
                    'total' => $pelaksanaans->total(),
                    'next_page_url' => $pelaksanaans->nextPageUrl(),
                    'prev_page_url' => $pelaksanaans->previousPageUrl(),
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
            'sop_id' => 'required|integer|exists:m_sop,id',
            'user_id' => 'required|integer|exists:m_user,id',
            'area_id' => 'nullable|integer|exists:m_area,id',
            'ruang_id' => 'nullable|integer|exists:m_ruang,id',
            'sop_langkah_id' => 'nullable|integer|exists:m_sop_langkah,id',
            'status_sop' => 'nullable|integer|in:0,1,2,3',
            'poin' => 'nullable|integer',
            'des' => 'nullable|string',
            'url' => 'nullable|string',
            'deadline_waktu' => 'nullable|numeric',
            'toleransi_waktu_sebelum' => 'nullable|numeric',
            'toleransi_waktu_sesudah' => 'nullable|numeric',
            'waktu_mulai' => 'nullable|numeric',
            'waktu_selesai' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $pelaksanaan = SopPelaksana::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Pelaksanaan SOP recorded successfully',
            'data' => $pelaksanaan->load(['sop', 'langkah', 'user', 'area', 'ruang'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pelaksanaan = SopPelaksana::with(['sop.kategori', 'langkah', 'user', 'area', 'ruang'])->find($id);

        if (!$pelaksanaan) {
            return response()->json([
                'success' => false,
                'message' => 'Pelaksanaan SOP not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Details retrieved successfully',
            'data' => $pelaksanaan
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $pelaksanaan = SopPelaksana::find($id);

        if (!$pelaksanaan) {
            return response()->json([
                'success' => false,
                'message' => 'Pelaksanaan SOP not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'sop_id' => 'required|integer|exists:m_sop,id',
            'user_id' => 'required|integer|exists:m_user,id',
            'area_id' => 'nullable|integer|exists:m_area,id',
            'ruang_id' => 'nullable|integer|exists:m_ruang,id',
            'sop_langkah_id' => 'nullable|integer|exists:m_sop_langkah,id',
            'status_sop' => 'nullable|integer|in:0,1,2,3',
            'poin' => 'nullable|integer',
            'des' => 'nullable|string',
            'url' => 'nullable|string',
            'deadline_waktu' => 'nullable|numeric',
            'toleransi_waktu_sebelum' => 'nullable|numeric',
            'toleransi_waktu_sesudah' => 'nullable|numeric',
            'waktu_mulai' => 'nullable|numeric',
            'waktu_selesai' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $pelaksanaan->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Pelaksanaan SOP updated successfully',
            'data' => $pelaksanaan->load(['sop', 'langkah', 'user', 'area', 'ruang'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $pelaksanaan = SopPelaksana::find($id);

        if (!$pelaksanaan) {
            return response()->json([
                'success' => false,
                'message' => 'Pelaksanaan SOP not found'
            ], 404);
        }

        $pelaksanaan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Pelaksanaan SOP deleted successfully'
        ]);
    }
}
