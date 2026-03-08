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

        // Fetch execution data for today
        $completedToday = SopPelaksana::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->whereNotNull('waktu_selesai')
            ->pluck('sop_langkah_id')
            ->toArray();

        $inProgressToday = SopPelaksana::where('user_id', $user->id)
            ->whereDate('created_at', $today)
            ->whereNotNull('waktu_mulai')
            ->whereNull('waktu_selesai')
            ->pluck('sop_langkah_id')
            ->toArray();

        $relevantSopIds = array_unique(array_merge($sopIds, SopLangkah::whereIn('id', $assignedStepIds)->pluck('sop_id')->toArray()));

        $sops = Sop::whereIn('id', $relevantSopIds)->with([
            'langkah' => function ($q) use ($assignedStepIds) {
                $q->whereIn('id', $assignedStepIds)->orderBy('urutan', 'asc');
            }
        ])->get();

        $result = [];
        foreach ($sops as $sop) {
            $steps = [];
            $countDone = 0;

            foreach ($sop->langkah as $langkah) {
                $status = 'belum_dikerjakan';
                if (in_array($langkah->id, $completedToday)) {
                    $status = 'selesai';
                    $countDone++;
                } else if (in_array($langkah->id, $inProgressToday)) {
                    $status = 'sedang_dikerjakan';
                }

                $steps[] = [
                    'id' => $langkah->id,
                    'urutan' => $langkah->urutan,
                    'deskripsi_langkah' => $langkah->deskripsi_langkah,
                    'poin' => $langkah->poin,
                    'status' => $status,
                    'wajib' => $langkah->wajib,
                ];
            }

            $percentage = count($steps) > 0 ? round(($countDone / count($steps)) * 100) : 0;

            $result[] = [
                'id' => $sop->id,
                'kode' => $sop->kode,
                'nama' => $sop->nama,
                'progress_percentage' => $percentage,
                'langkah_selesai' => $countDone,
                'total_langkah' => count($steps),
                'langkah' => $steps,
            ];
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar tugas hari ini berhasil diambil',
            'data' => $result
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
