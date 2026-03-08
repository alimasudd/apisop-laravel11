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

        // Fetch ALL execution data for the history tab (Jadwal Pelaksanaan)
        $pelaksanaanHistory = SopPelaksana::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Fetch execution data ONLY for today for the task tracking logic
        $pelaksanaanHariIni = $pelaksanaanHistory->filter(function ($p) use ($today) {
            // Check if created_at is today. Fallback to current date if column missing/empty
            try {
                return Carbon::parse($p->created_at)->isToday();
            } catch (\Exception $e) {
                return false;
            }
        });

        $completedTodayIds = $pelaksanaanHariIni->whereNotNull('waktu_selesai')->pluck('sop_langkah_id')->toArray();
        $inProgressTodayIds = $pelaksanaanHariIni->whereNull('waktu_selesai')->whereNotNull('waktu_mulai')->pluck('sop_langkah_id')->toArray();

        $selesaiCount = count(array_intersect($assignedStepIds, $completedTodayIds));
        $dikerjakanCount = count(array_intersect($assignedStepIds, $inProgressTodayIds));
        $belumCount = max(0, $totalLangkah - $selesaiCount - $dikerjakanCount);

        $poinHariIni = $pelaksanaanHariIni->sum('poin');

        $relevantSopIds = array_unique(array_merge($sopIds, SopLangkah::whereIn('id', $assignedStepIds)->pluck('sop_id')->toArray()));

        // --- Build Tugas Hari Ini (Logic with Status) ---
        $sops = Sop::whereIn('id', $relevantSopIds)->with([
            'kategori',
            'langkah' => function ($q) use ($assignedStepIds) {
                $q->whereIn('id', $assignedStepIds)->with('ruang')->orderBy('urutan', 'asc');
            }
        ])->get();

        $tugasList = [];
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

            $tugasList[] = [
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

        // --- Build Jadwal Pelaksanaan (History Logic) ---
        // Group by SOP code or ID to show what was executed
        $historySopIds = $pelaksanaanHistory->pluck('sop_id')->unique()->toArray();
        $historySops = Sop::whereIn('id', $historySopIds)->with('kategori', 'langkah.ruang')->get();

        $jadwalList = [];
        foreach ($historySops as $sop) {
            $sopPelaksanaan = $pelaksanaanHistory->where('sop_id', $sop->id);

            $steps = [];
            foreach ($sop->langkah as $langkah) {
                $p = $sopPelaksanaan->where('sop_langkah_id', $langkah->id)->first();
                if (!$p)
                    continue; // Only show steps that were actually executed in history

                $status = 'belum_dikerjakan';
                if ($p->waktu_selesai)
                    $status = 'selesai';
                else if ($p->waktu_mulai)
                    $status = 'sedang_dikerjakan';

                $steps[] = [
                    'id' => $langkah->id,
                    'urutan' => $langkah->urutan,
                    'deskripsi_langkah' => $langkah->deskripsi_langkah,
                    'ruang_nama' => $langkah->ruang ? $langkah->ruang->nama : '-',
                    'status' => $status,
                    'tanggal' => Carbon::parse($p->created_at)->format('d M Y H:i'),
                ];
            }

            if (empty($steps))
                continue;

            $jadwalList[] = [
                'id' => $sop->id,
                'kode' => $sop->kode,
                'nama' => $sop->nama,
                'kategori_nama' => $sop->kategori ? $sop->kategori->nama : '-',
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
                    'jadwal_pelaksanaan' => count($jadwalList)
                ],
                'tugas_hari_ini' => $tugasList,
                'jadwal_pelaksanaan' => $jadwalList
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
        $todayStr = Carbon::today()->toDateString();
        $existing = SopPelaksana::where('user_id', $user->id)
            ->where('sop_langkah_id', $langkah_id)
            ->where('created_at', 'like', $todayStr . '%')
            ->first();

        if ($existing) {
            if ($existing->waktu_selesai) {
                return response()->json(['success' => false, 'message' => 'Langkah ini sudah diselesaikan hari ini'], 400);
            }
            return response()->json(['success' => false, 'message' => 'Langkah ini sedang dikerjakan hari ini', 'data' => $existing], 400);
        }

        try {
            $pelaksana = SopPelaksana::create([
                'user_id' => $user->id,
                'sop_id' => $langkah->sop_id,
                'sop_langkah_id' => $langkah_id,
                'ruang_id' => $langkah->ruang_id,
                'waktu_mulai' => time(),
                'status_sop' => 0,
                'poin' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mulai mengerjakan langkah',
                'data' => $pelaksana
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal simpan pelaksanaan: ' . $e->getMessage()
            ], 500);
        }
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
