<?php

namespace App\Http\Controllers\Api\Karyawan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class AccountController extends Controller
{
    /**
     * Get detail profil login
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'message' => 'Profil berhasil diambil',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'hp' => $user->hp,
                'jabatan' => 'Karyawan', // Default
                'status' => $user->status_aktif == 1 ? 'Aktif' : 'Tidak Aktif'
            ]
        ]);
    }

    /**
     * Update profil login (termasuk Ganti Password opsional via 1 form)
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'hp' => 'required|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ], [
            'nama.required' => 'Nama lengkap wajib diisi.',
            'hp.required' => 'Nomor WhatsApp wajib diisi.',
            'password.min' => 'Password baru minimal harus 6 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak sesuai.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat kesalahan pada inputan Anda.',
                'data' => $validator->errors()
            ], 422);
        }

        $user->nama = $request->nama;
        $user->hp = $request->hp;

        // Jika user mengisi form password, berarti mau ganti sekalian
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil Anda berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'nama' => $user->nama,
                'email' => $user->email,
                'hp' => $user->hp,
                'jabatan' => 'Karyawan',
                'status' => $user->status_aktif == 1 ? 'Aktif' : 'Tidak Aktif'
            ]
        ]);
    }

    /**
     * Ganti password user login
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'password_lama' => 'required',
            'password_baru' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'data' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->password_lama, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password lama tidak sesuai'
            ], 400);
        }

        $userToUpdate = User::find($user->id);
        $userToUpdate->password = Hash::make($request->password_baru);
        $userToUpdate->save();

        return response()->json([
            'success' => true,
            'message' => 'Password berhasil diubah'
        ]);
    }
}
