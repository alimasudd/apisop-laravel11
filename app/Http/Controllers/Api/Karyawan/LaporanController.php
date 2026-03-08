<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SopPelaksana;

class LaporanController extends Controller
{
    /**
     * Get riwayat laporan pelaksanaan (SOP selesai) oleh user login
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $laporan = SopPelaksana::where('user_id', $user->id)
            ->with(['sop.kategori', 'langkah', 'ruang'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Riwayat laporan berhasil diambil',
            'data' => [
                'items' => $laporan->items(),
                'pagination' => [
                    'current_page' => $laporan->currentPage(),
                    'last_page' => $laporan->lastPage(),
                    'per_page' => $laporan->perPage(),
                    'total' => $laporan->total(),
                    'next_page_url' => $laporan->nextPageUrl(),
                    'prev_page_url' => $laporan->previousPageUrl(),
                ]
            ]
        ]);
    }
}
