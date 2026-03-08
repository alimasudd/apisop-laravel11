<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sop;
use App\Models\SopTugas;
use App\Models\SopPelaksana;
use App\Models\SopLangkah;
use Carbon\Carbon;

class TugasController extends Controller
{
    /**
     * Get daftar tugas yang harus dikerjakan user hari ini.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $today = Carbon::today();

        $tugas = SopTugas::where('user_id', $user->id)->get();

        $assignedStepIds = [];
        $sopIds = [];

        foreach ($tugas as $t) {
            if ($t->sop_langkah_id) {
                $assignedStepIds[] = (int) $t->sop_langkah_id;
            } else {
                $sopIds[] = (int) $t->sop_id;
                $stepIds = SopLangkah::where('sop_id', $t->sop_id)->pluck('id')->toArray();
                $assignedStepIds = array_merge($assignedStepIds, $stepIds);
            }
        }
        $assignedStepIds = array_unique($assignedStepIds);

        $totalLangkah = count($assignedStepIds);

        // Fetch execution data for today. 
        // We assume 'created_at' exists as a DATE or DATETIME if not failed on that.
        // If it still fails, we might need to check the actual column name for date of execution.
        $pelaksanaanHariIni = SopPelaksana::where('user_id', $user->id)
            ->where(function ($q) use ($today) {
                $q->whereDate('created_at', $today);
            })
            ->get();

        $completedTodayIds = $pelaksanaanHariIni->whereNotNull('waktu_selesai')->pluck('sop_langkah_id')->toArray();
        $inProgressTodayIds = $pelaksanaanHariIni->whereNull('waktu_selesai')->whereNotNull('waktu_mulai')->pluck('sop_langkah_id')->toArray();

        $selesaiCount = count(array_intersect($assignedStepIds, $completedTodayIds));
        $dikerjakanCount = count(array_intersect($assignedStepIds, $inProgressTodayIds));
        $belumCount = max(0, $totalLangkah - $selesaiCount - $dikerjakanCount);

        $poinHariIni = $pelaksanaanHariIni->sum('poin');

        $relevantSopIds = array_unique(array_merge($sopIds, SopLangkah::whereIn('id', $assignedStepIds)->pluck('sop_id')->toArray()));

        $sops = Sop::whereIn('id', $relevantSopIds)->with([
            'kategori',
            'langkah' => function ($q) use ($assignedStepIds) {
                $q->whereIn('id', $assignedStepIds)->with('ruang')->orderBy('urutan', 'asc');
            }
        ])->get();

        $resultList = [];
        foreach ($sops as $sop) {
            $steps = [];
            $countDone = 0;
            $countInProgress = 0;
            $countBelum = 0;

            foreach ($sop->langkah as $langkah) {
                $status = 'belum_dikerjakan';
                $waktuMulaiStr = null;

                $pelaksanaan = $pelaksanaanHariIni->where('sop_langkah_id', $langkah->id)->first();

                if (in_array($langkah->id, $completedTodayIds)) {
                    $status = 'selesai';
                    $countDone++;
                } else if (in_array($langkah->id, $inProgressTodayIds)) {
                    $status = 'sedang_dikerjakan';
                    $countInProgress++;
                    if ($pelaksanaan && $pelaksanaan->waktu_mulai) {
                        $waktuMulaiStr = Carbon::createFromTimestamp($pelaksanaan->waktu_mulai)->format('H:i');
                    }
                } else {
                    $countBelum++;
                }

                $steps[] = [
                    'id' => $langkah->id,
                    'urutan' => $langkah->urutan,
                    'deskripsi_langkah' => $langkah->deskripsi_langkah,
                    'ruang_nama' => $langkah->ruang ? $langkah->ruang->nama : '-',
                    'poin' => $langkah->poin,
                    'status' => $status,
                    'wajib' => $langkah->wajib,
                    'waktu_mulai' => $waktuMulaiStr
                ];
            }

            $percentage = count($steps) > 0 ? round(($countDone / count($steps)) * 100) : 0;

            $resultList[] = [
                'id' => $sop->id,
                'kode' => $sop->kode,
                'nama' => $sop->nama,
                'kategori_nama' => $sop->kategori ? $sop->kategori->nama : '-',
                'progress' => [
                    'percentage' => $percentage,
                    'selesai' => $countDone,
                    'dikerjakan' => $countInProgress,
                    'belum' => $countBelum,
                    'total_langkah' => count($steps),
                ],
                'langkah' => $steps,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar tugas hari ini berhasil diambil',
            'data' => [
                'summary' => [
                    'total_langkah' => $totalLangkah,
                    'selesai' => $selesaiCount,
                    'dikerjakan' => $dikerjakanCount,
                    'belum' => $belumCount,
                    'poin_hari_ini' => (int) $poinHariIni,
                    'jadwal_pelaksanaan' => count($resultList) // For now same as tugas_hari_ini to test UI tabs
                ],
                'tugas_hari_ini' => $resultList,
                'jadwal_pelaksanaan' => $resultList // Add this to prevent null/empty on the other tab
            ]
        ]);
    }

    /**
     * API untuk mulai kerjakan langkah (check-in)
     */
    public function mulai(Request $request, $langkah_id)
    {
        $user = $request->user();
        $langkah = SopLangkah::find($langkah_id);

        if (!$langkah) {
            return response()->json(['success' => false, 'message' => 'Langkah tidak ditemukan'], 404);
        }

        // Cek apakah sudah dikerjakan hari ini
        $today = Carbon::today();
        $existing = SopPelaksana::where('user_id', $user->id)
            ->where('sop_langkah_id', $langkah_id)
            ->whereDate('created_at', $today)
            ->first();

        if ($existing) {
            if ($existing->waktu_selesai) {
                return response()->json(['success' => false, 'message' => 'Langkah ini sudah diselesaikan hari ini'], 400);
            }
            return response()->json(['success' => false, 'message' => 'Langkah ini sedang dikerjakan hari ini', 'data' => $existing], 400);
        }

        $pelaksana = SopPelaksana::create([
            'user_id' => $user->id,
            'sop_id' => $langkah->sop_id,
            'sop_langkah_id' => $langkah_id,
            'ruang_id' => $langkah->ruang_id,
            'waktu_mulai' => time(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mulai mengerjakan langkah',
            'data' => $pelaksana
        ]);
    }

    /**
     * API untuk selesaikan langkah (check-out/submit)
     */
    public function selesai(Request $request, $langkah_id)
    {
        $user = $request->user();
        $today = Carbon::today();

        $pelaksana = SopPelaksana::where('user_id', $user->id)
            ->where('sop_langkah_id', $langkah_id)
            ->whereDate('created_at', $today)
            ->first();

        if (!$pelaksana) {
            return response()->json(['success' => false, 'message' => 'Anda belum memulai langkah ini hari ini. Silakan mulai terlebih dahulu.'], 400);
        }

        if ($pelaksana->waktu_selesai) {
            return response()->json(['success' => false, 'message' => 'Langkah ini sudah diselesaikan sebelumnya.'], 400);
        }

        $langkah = SopLangkah::find($langkah_id);

        $pelaksana->waktu_selesai = time();
        $pelaksana->des = $request->des ?? '';
        $pelaksana->url = $request->url; // foto/video bukti
        $pelaksana->poin = $langkah->poin;
        $pelaksana->save();

        return response()->json([
            'success' => true,
            'message' => 'Langkah berhasil diselesaikan',
            'data' => $pelaksana
        ]);
    }
}
