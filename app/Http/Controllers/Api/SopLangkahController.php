<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SopLangkah;
use Illuminate\Support\Facades\Validator;

class SopLangkahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $sop_id = $request->query('sop_id');
        $wajib = $request->query('wajib');

        $langkahs = SopLangkah::query()
            ->with(['sop.kategori', 'ruang', 'user'])
            ->when($sop_id, function ($query, $sop_id) {
                $query->where('sop_id', $sop_id);
            })
            ->when($wajib !== null && $wajib !== '', function ($query) use ($wajib) {
                $query->where('wajib', $wajib);
            })
            ->when($search, function ($query, $search) {
                $query->where('deskripsi_langkah', 'like', "%{$search}%")
                    ->orWhereHas('sop', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%")
                            ->orWhere('kode', 'like', "%{$search}%");
                    })
                    ->orWhereHas('ruang', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    });
            })
            ->orderBy('sop_id')
            ->orderBy('urutan')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'langkahs' => $langkahs->items(),
                'pagination' => [
                    'current_page' => $langkahs->currentPage(),
                    'last_page' => $langkahs->lastPage(),
                    'per_page' => $langkahs->perPage(),
                    'total' => $langkahs->total(),
                    'next_page_url' => $langkahs->nextPageUrl(),
                    'prev_page_url' => $langkahs->previousPageUrl(),
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
            'ruang_id' => 'nullable|integer|exists:m_ruang,id',
            'user_id' => 'nullable|integer|exists:m_user,id',
            'urutan' => 'required|integer',
            'deskripsi_langkah' => 'required|string',
            'wajib' => 'required|boolean',
            'poin' => 'nullable|integer',
            'deadline_waktu' => 'nullable|integer',
            'toleransi_waktu_sebelum' => 'nullable|integer',
            'toleransi_waktu_sesudah' => 'nullable|integer',
            'wa_reminder' => 'nullable|boolean',
            'wa_jam_kirim' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $langkah = SopLangkah::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Langkah SOP created successfully',
            'data' => $langkah->load(['sop', 'ruang', 'user'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $langkah = SopLangkah::with(['sop', 'ruang', 'user'])->find($id);

        if (!$langkah) {
            return response()->json([
                'success' => false,
                'message' => 'Langkah SOP not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Langkah SOP details retrieved successfully',
            'data' => $langkah
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $langkah = SopLangkah::find($id);

        if (!$langkah) {
            return response()->json([
                'success' => false,
                'message' => 'Langkah SOP not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'sop_id' => 'required|integer|exists:m_sop,id',
            'ruang_id' => 'nullable|integer|exists:m_ruang,id',
            'user_id' => 'nullable|integer|exists:m_user,id',
            'urutan' => 'required|integer',
            'deskripsi_langkah' => 'required|string',
            'wajib' => 'required|boolean',
            'poin' => 'nullable|integer',
            'deadline_waktu' => 'nullable|integer',
            'toleransi_waktu_sebelum' => 'nullable|integer',
            'toleransi_waktu_sesudah' => 'nullable|integer',
            'wa_reminder' => 'nullable|boolean',
            'wa_jam_kirim' => 'nullable|string|max:5',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $langkah->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Langkah SOP updated successfully',
            'data' => $langkah->load(['sop', 'ruang', 'user'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $langkah = SopLangkah::find($id);

        if (!$langkah) {
            return response()->json([
                'success' => false,
                'message' => 'Langkah SOP not found'
            ], 404);
        }

        $langkah->delete();

        return response()->json([
            'success' => true,
            'message' => 'Langkah SOP deleted successfully'
        ]);
    }
}
