<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sop;
use App\Models\SopTugas;
use App\Models\SopPelaksana;
use App\Models\SopLangkah;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        // 1. Dapatkan daftar ID langkah yang ditugaskan ke user
        // Karena SopTugas bisa menunjuk ke satu SOP secara utuh (sop_langkah_id NULL) 
        // atau ke langkah tertentu saja.
        $tugas = SopTugas::where('user_id', $user->id)->get();

        $assignedStepIds = [];
        $sopIds = [];

        foreach ($tugas as $t) {
            if ($t->sop_langkah_id) {
                $assignedStepIds[] = (int) $t->sop_langkah_id;
            } else {
                $sopIds[] = (int) $t->sop_id;
                // Ambil semua langkah untuk SOP ini
                $stepIds = SopLangkah::where('sop_id', $t->sop_id)->pluck('id')->toArray();
                $assignedStepIds = array_merge($assignedStepIds, $stepIds);
            }
        }
        $assignedStepIds = array_unique($assignedStepIds);
        $totalLangkah = count($assignedStepIds);

        // 2. Dapatkan langkah yang sudah selesai hari ini
        $completedToday = SopPelaksana::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->whereNotNull('waktu_selesai')
            ->pluck('sop_langkah_id')
            ->map(fn($id) => (int) $id)
            ->toArray();

        $selesaiHariIni = count(array_intersect($assignedStepIds, $completedToday));

        // 3. Sedang dikerjakan (sudah mulai tapi belum selesai)
        $inProgressCount = SopPelaksana::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->whereNotNull('waktu_mulai')
            ->whereNull('waktu_selesai')
            ->count();

        $belumDikerjakan = max(0, $totalLangkah - $selesaiHariIni - $inProgressCount);

        // 4. Poin hari ini
        $poinHariIni = SopPelaksana::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->sum('poin');

        // 5. Total poin keseluruhan
        $totalPoinKeseluruhan = SopPelaksana::where('user_id', $user->id)
            ->sum('poin');

        // 6. Tugas SOP Hari Ini (Dikelompokkan per SOP untuk UI)
        $tugasSopHariIni = [];
        $relevantSopIds = array_unique(array_merge($sopIds, SopLangkah::whereIn('id', $assignedStepIds)->pluck('sop_id')->toArray()));

        $sops = Sop::whereIn('id', $relevantSopIds)->with([
            'langkah' => function ($q) use ($assignedStepIds) {
                $q->whereIn('id', $assignedStepIds);
            }
        ])->get();

        foreach ($sops as $sop) {
            $sopSteps = $sop->langkah->pluck('id')->map(fn($id) => (int) $id)->toArray();
            $doneSteps = array_intersect($sopSteps, $completedToday);

            $countDone = count($doneSteps);
            $countTotal = count($sopSteps);
            $percentage = $countTotal > 0 ? round(($countDone / $countTotal) * 100) : 0;

            $tugasSopHariIni[] = [
                'id' => $sop->id,
                'kode' => $sop->kode,
                'nama' => $sop->nama,
                'progress_text' => "$countDone/$countTotal langkah hari ini",
                'percentage' => $percentage,
            ];
        }

        // 7. Aktivitas Hari Ini (Log pelaksanaan)
        $aktivitasHariIni = SopPelaksana::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->with(['langkah', 'sop'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'kegiatan' => "Mengerjakan Langkah " . ($item->langkah ? $item->langkah->urutan : "?"),
                    'deskripsi' => $item->langkah ? $item->langkah->deskripsi_langkah : $item->sop->nama,
                    'waktu' => Carbon::parse($item->created_at)->format('H:i'),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'user' => [
                    'nama' => $user->nama,
                    'email' => $user->email,
                    'hari_ini' => $today->translatedFormat('l, d M Y'),
                ],
                'summary' => [
                    'persentase_hari_ini' => $totalLangkah > 0 ? round(($selesaiHariIni / $totalLangkah) * 100) : 0,
                    'total_langkah' => $totalLangkah,
                    'selesai_hari_ini' => $selesaiHariIni,
                    'sedang_dikerjakan' => $inProgressCount,
                    'belum_dikerjakan' => $belumDikerjakan,
                    'poin_hari_ini' => (int) $poinHariIni,
                    'total_poin_keseluruhan' => (int) $totalPoinKeseluruhan,
                ],
                'tugas_hari_ini' => $tugasSopHariIni,
                'aktivitas_hari_ini' => $aktivitasHariIni,
            ]
        ], 200);
    }
}
