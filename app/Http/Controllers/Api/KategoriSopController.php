<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KategoriSop;
use Illuminate\Support\Facades\Validator;

class KategoriSopController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $kategoris = KategoriSop::query()
            ->withCount('sops') // To show total SOPs as indicated in UI
            ->when($search, function ($query, $search) {
                $query->where('nama', 'like', "%{$search}%")
                    ->orWhere('kode', 'like', "%{$search}%")
                    ->orWhere('deskripsi', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'kategori_sops' => $kategoris->items(),
                'pagination' => [
                    'current_page' => $kategoris->currentPage(),
                    'last_page' => $kategoris->lastPage(),
                    'per_page' => $kategoris->perPage(),
                    'total' => $kategoris->total(),
                    'next_page_url' => $kategoris->nextPageUrl(),
                    'prev_page_url' => $kategoris->previousPageUrl(),
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
            'kode' => 'required|string|max:20|unique:m_kat_sop',
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'status' => 'nullable|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $kategori = KategoriSop::create([
            'kode' => $request->kode,
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi,
            'status' => $request->status ?? 'aktif',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori SOP created successfully',
            'data' => $kategori
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $kategori = KategoriSop::with('sops')->find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori SOP not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Kategori SOP details retrieved successfully',
            'data' => $kategori
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $kategori = KategoriSop::find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori SOP not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'kode' => 'required|string|max:20|unique:m_kat_sop,kode,' . $id,
            'nama' => 'required|string|max:100',
            'deskripsi' => 'nullable|string',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $kategori->update([
            'kode' => $request->kode,
            'nama' => $request->nama,
            'deskripsi' => $request->deskripsi,
            'status' => $request->status,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Kategori SOP updated successfully',
            'data' => $kategori
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $kategori = KategoriSop::find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori SOP not found'
            ], 404);
        }

        $kategori->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kategori SOP deleted successfully'
        ]);
    }

    /**
     * Show list of SOPs for a specific Category.
     * (As seen in the "Daftar SOP" modal in UI)
     */
    public function sops(string $id)
    {
        $kategori = KategoriSop::with('sops')->find($id);

        if (!$kategori) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori SOP not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'SOP list retrieved successfully',
            'data' => [
                'nama_kategori' => $kategori->nama,
                'sops' => $kategori->sops
            ]
        ]);
    }
}
