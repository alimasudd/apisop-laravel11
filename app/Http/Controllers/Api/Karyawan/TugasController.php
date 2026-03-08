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

        // Get all assignments for this user
        $assignments = SopTugas::where('user_id', $user->id)->get();

        $allAssignedSteps = [];
        $sopDetailsMap = [];

        foreach ($assignments as $a) {
            $sopId = (int) $a->sop_id;
            if ($a->sop_langkah_id) {
                $allAssignedSteps[$sopId][] = (int) $a->sop_langkah_id;
            } else {
                // If entire SOP is assigned, get all its steps
                $steps = SopLangkah::where('sop_id', $sopId)->pluck('id')->toArray();
                $allAssignedSteps[$sopId] = array_merge($allAssignedSteps[$sopId] ?? [], $steps);
            }
        }

        // Execution data for today
        // Note: we assume executions are recorded in m_sop_pelaksana
        $executionsToday = SopPelaksana::where('user_id', $user->id)
            ->where(function ($q) use ($today) {
                $q->whereDate('created_at', $today);
                // Fallback if created_at is missing but it's recorded as time() in waktu_mulai
                $startOfDay = $today->timestamp;
                $endOfDay = $today->copy()->endOfDay()->timestamp;
                $q->orWhereBetween('waktu_mulai', [$startOfDay, $endOfDay]);
            })
            ->get();

        $completedIds = $executionsToday->whereNotNull('waktu_selesai')->pluck('sop_langkah_id')->toArray();
        $inProgressIds = $executionsToday->whereNull('waktu_selesai')->whereNotNull('waktu_mulai')->pluck('sop_langkah_id')->toArray();

        $tugasHariIni = [];
        $jadwalPelaksanaan = [];

        $sopIds = array_keys($allAssignedSteps);
        $sops = Sop::whereIn('id', $sopIds)->with(['kategori', 'langkah.ruang'])->get();

        foreach ($sops as $sop) {
            $assignedStepIdsForThisSop = array_unique($allAssignedSteps[$sop->id] ?? []);

            $stepsData = [];
            $sopDone = 0;
            $sopInProgress = 0;
            $sopPending = 0;

            // Load all steps for this SOP that are assigned to this user
            foreach ($sop->langkah as $langkah) {
                if (!in_array($langkah->id, $assignedStepIdsForThisSop))
                    continue;

                $status = 'belum_dikerjakan';
                $waktuMulai = null;

                $exec = $executionsToday->where('sop_langkah_id', $langkah->id)->first();
                if (in_array($langkah->id, $completedIds)) {
                    $status = 'selesai';
                    $sopDone++;
                } else if (in_array($langkah->id, $inProgressIds)) {
                    $status = 'sedang_dikerjakan';
                    $sopInProgress++;
                    if ($exec && $exec->waktu_mulai) {
                        $waktuMulai = Carbon::createFromTimestamp($exec->waktu_mulai)->format('H:i');
                    }
                } else {
                    $sopPending++;
                }

                $stepsData[] = [
                    'id' => $langkah->id,
                    'urutan' => $langkah->urutan,
                    'deskripsi_langkah' => $langkah->deskripsi_langkah,
                    'ruang_nama' => $langkah->ruang ? $langkah->ruang->nama : '-',
                    'poin' => (int) $langkah->poin,
                    'status' => $status,
                    'wajib' => (bool) $langkah->wajib,
                    'waktu_mulai' => $waktuMulai
                ];
            }

            if (empty($stepsData))
                continue;

            $percentage = round(($sopDone / count($stepsData)) * 100);

            $sopData = [
                'id' => $sop->id,
                'kode' => $sop->kode,
                'nama' => $sop->nama,
                'kategori_nama' => $sop->kategori ? $sop->kategori->nama : '-',
                'progress' => [
                    'percentage' => $percentage,
                    'selesai' => $sopDone,
                    'dikerjakan' => $sopInProgress,
                    'belum' => $sopPending,
                    'total_langkah' => count($stepsData),
                ],
                'langkah' => $stepsData,
            ];

            // For now, put all assigned tasks in Tugas Hari Ini
            $tugasHariIni[] = $sopData;

            // If it's not started yet, it could also be in Schedules? 
            // Or maybe there's another logic for Schedules. 
            // The user says they "sudah buatkan", maybe they assigned it to a future date?
            // But m_sop_tugas has no date. 
            // Let's assume Jadwal Pelaksanaan is anything that is NOT completed today.
            if ($sopDone < count($stepsData)) {
                $jadwalPelaksanaan[] = $sopData;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_langkah' => collect($tugasHariIni)->sum(fn($s) => $s['progress']['total_langkah']),
                    'selesai' => collect($tugasHariIni)->sum(fn($s) => $s['progress']['selesai']),
                    'dikerjakan' => collect($tugasHariIni)->sum(fn($s) => $s['progress']['dikerjakan']),
                    'belum' => collect($tugasHariIni)->sum(fn($s) => $s['progress']['belum']),
                    'poin_hari_ini' => (int) $executionsToday->sum('poin'),
                    'jadwal_pelaksanaan' => count($jadwalPelaksanaan)
                ],
                'tugas_hari_ini' => $tugasHariIni,
                'jadwal_pelaksanaan' => $jadwalPelaksanaan
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
