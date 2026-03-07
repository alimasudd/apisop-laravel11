<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SopTugas;
use App\Models\Sop;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class SopTugasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');

        $tugas = SopTugas::query()
            ->with(['sop.kategori', 'langkah', 'user'])
            ->when($search, function ($query, $search) {
                $query->whereHas('sop', function ($q) use ($search) {
                    $q->where('nama', 'like', "%{$search}%")
                        ->orWhere('kode', 'like', "%{$search}%");
                })
                    ->orWhereHas('user', function ($q) use ($search) {
                        $q->where('nama', 'like', "%{$search}%");
                    })
                    ->orWhereHas('langkah', function ($q) use ($search) {
                        $q->where('deskripsi_langkah', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => [
                'tugas' => $tugas->items(),
                'pagination' => [
                    'current_page' => $tugas->currentPage(),
                    'last_page' => $tugas->lastPage(),
                    'per_page' => $tugas->perPage(),
                    'total' => $tugas->total(),
                    'next_page_url' => $tugas->nextPageUrl(),
                    'prev_page_url' => $tugas->previousPageUrl(),
                ]
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage (Handles Single and Mass Assignment).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sop_id' => 'required|integer|exists:m_sop,id',
            'user_ids' => 'required|array',
            'user_ids.*' => 'integer|exists:m_user,id',
            'ditugaskan_pada' => 'required|in:semua,tertentu',
            'sop_langkah_ids' => 'required_if:ditugaskan_pada,tertentu|array',
            'sop_langkah_ids.*' => 'integer|exists:m_sop_langkah,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'data' => $validator->errors()
            ], 422);
        }

        $sop_id = $request->sop_id;
        $user_ids = $request->user_ids;
        $ditugaskan_pada = $request->ditugaskan_pada;
        $sop_langkah_ids = $request->sop_langkah_ids ?? [null];

        if ($ditugaskan_pada === 'semua') {
            $sop_langkah_ids = [null];
        }

        DB::beginTransaction();
        try {
            $createdCount = 0;
            foreach ($user_ids as $user_id) {
                foreach ($sop_langkah_ids as $langkah_id) {
                    // Check if already assigned to avoid duplicates (as mentioned in UI "akan dilewati otomatis")
                    $exists = SopTugas::where('sop_id', $sop_id)
                        ->where('user_id', $user_id)
                        ->where('sop_langkah_id', $langkah_id)
                        ->exists();

                    if (!$exists) {
                        SopTugas::create([
                            'sop_id' => $sop_id,
                            'user_id' => $user_id,
                            'sop_langkah_id' => $langkah_id,
                        ]);
                        $createdCount++;
                    }
                }
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully assigned to {$createdCount} user/step combinations",
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Failed to process assignments',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tugas = SopTugas::find($id);

        if (!$tugas) {
            return response()->json([
                'success' => false,
                'message' => 'Tugas not found'
            ], 404);
        }

        $tugas->delete();

        return response()->json([
            'success' => true,
            'message' => 'Assignment deleted successfully'
        ]);
    }
}
