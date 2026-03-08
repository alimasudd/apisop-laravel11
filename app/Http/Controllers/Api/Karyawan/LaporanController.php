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

        // Default: 1st of current month to end of current month if not provided
        $startDate = $request->query('start_date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->query('end_date', \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'));

        $query = SopPelaksana::where('user_id', $user->id)
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->with(['sop', 'langkah', 'ruang'])
            ->orderBy('created_at', 'desc');

        $laporanList = $query->get();

        // Summary Stats
        $langkahSelesai = $laporanList->whereNotNull('waktu_selesai')->count();
        $totalPoin = $laporanList->whereNotNull('waktu_selesai')->sum('poin');

        $hariAktif = $laporanList->map(function ($item) {
            return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d');
        })->unique()->count();

        $sopDikerjakan = $laporanList->pluck('sop_id')->unique()->count();

        // Grouping Data
        $groupedByDate = $laporanList->groupBy(function ($item) {
            return \Carbon\Carbon::parse($item->created_at)->format('Y-m-d');
        });

        $riwayatGrouped = [];

        foreach ($groupedByDate as $date => $items) {
            $selesaiCount = $items->whereNotNull('waktu_selesai')->count();
            // Assuming points are only awarded / valid if completed, though logic might already ensure it
            $poinDate = $items->sum('poin'); // Sum points for this group

            $listLangkah = [];
            foreach ($items as $idx => $item) {
                $status = 'Proses';
                if ($item->waktu_selesai) {
                    $status = 'Selesai';
                }

                $listLangkah[] = [
                    'id' => $item->id,
                    'sop_kode' => $item->sop ? $item->sop->kode : '-',
                    'sop_nama' => $item->sop ? $item->sop->nama : 'SOP Telah Dihapus',
                    'langkah_nama' => $item->langkah ? $item->langkah->deskripsi_langkah : '-',
                    'wajib' => $item->langkah ? (bool) $item->langkah->wajib : false,
                    'ruang_nama' => $item->ruang ? $item->ruang->nama : '-',
                    'status' => $status,
                    'poin' => $item->poin,
                    'waktu_mulai' => $item->waktu_mulai ? \Carbon\Carbon::createFromTimestamp($item->waktu_mulai)->format('H:i') : '-',
                    'waktu_selesai' => $item->waktu_selesai ? \Carbon\Carbon::createFromTimestamp($item->waktu_selesai)->format('H:i') : '-',
                    'catatan' => $item->des ?: '-',
                ];
            }

            $riwayatGrouped[] = [
                'tanggal' => $date,
                // e.g. "Sunday, 08 March 2026"
                'tanggal_format' => \Carbon\Carbon::parse($date)->translatedFormat('l, d F Y'),
                'selesai_count' => $selesaiCount,
                'poin_date' => $poinDate,
                'items' => $listLangkah,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Laporan berhasil diambil',
            'data' => [
                'periode' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'summary' => [
                    'langkah_selesai' => $langkahSelesai,
                    'total_poin' => $totalPoin,
                    'hari_aktif' => $hariAktif,
                    'sop_dikerjakan' => $sopDikerjakan,
                ],
                'riwayat' => $riwayatGrouped
            ]
        ]);
    }
}
