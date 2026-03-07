<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sop;
use Illuminate\Support\Facades\Validator;

class SopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $sops = Sop::query()
            ->with(['kategori', 'pengawas'])
            ->withCount('langkah')
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%")
                    ->orWhereHas('kategori', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'sops' => $sops->items(),
                'pagination' => [
                    'current_page' => $sops->currentPage(),
                    'last_page' => $sops->lastPage(),
                    'per_page' => $sops->perPage(),
                    'total' => $sops->total(),
                    'next_page_url' => $sops->nextPageUrl(),
                    'prev_page_url' => $sops->previousPageUrl(),
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
            'katsop_id' => 'required|integer|exists:m_kat_sop,id',
            'kode' => 'required|string|max:30|unique:m_sop',
            'nama' => 'required|string|max:200',
            'deskripsi' => 'nullable|string',
            'versi' => 'nullable|string|max:20',
            'tanggal_berlaku' => 'nullable|date',
            'tanggal_kadaluarsa' => 'nullable|date',
            'status' => 'nullable|in:aktif,nonaktif,draft,expired',
            'status_sop' => 'required|in:mutlak,custom',
            'pengawas_id' => 'nullable|integer|exists:m_user,id',
            'total_poin' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $sop = Sop::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'SOP created successfully',
            'data' => $sop->load(['kategori', 'pengawas'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sop = Sop::with(['kategori', 'pengawas', 'langkah.ruang', 'langkah.user'])->find($id);

        if (!$sop) {
            return response()->json([
                'success' => false,
                'message' => 'SOP not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'SOP details retrieved successfully',
            'data' => $sop
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sop = Sop::find($id);

        if (!$sop) {
            return response()->json([
                'success' => false,
                'message' => 'SOP not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'katsop_id' => 'required|integer|exists:m_kat_sop,id',
            'kode' => 'required|string|max:30|unique:m_sop,kode,' . $id,
            'nama' => 'required|string|max:200',
            'deskripsi' => 'nullable|string',
            'versi' => 'nullable|string|max:20',
            'tanggal_berlaku' => 'nullable|date',
            'tanggal_kadaluarsa' => 'nullable|date',
            'status' => 'required|in:aktif,nonaktif,draft,expired',
            'status_sop' => 'required|in:mutlak,custom',
            'pengawas_id' => 'nullable|integer|exists:m_user,id',
            'total_poin' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $sop->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'SOP updated successfully',
            'data' => $sop->load(['kategori', 'pengawas'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sop = Sop::find($id);

        if (!$sop) {
            return response()->json([
                'success' => false,
                'message' => 'SOP not found'
            ], 404);
        }

        $sop->delete();

        return response()->json([
            'success' => true,
            'message' => 'SOP deleted successfully'
        ]);
    }
}
