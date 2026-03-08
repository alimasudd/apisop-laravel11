<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('m_sop_langkah', function (Blueprint $table) {
            $table->id();
            $table->integer('sop_id');
            $table->integer('ruang_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('jabatan_id')->nullable();
            $table->integer('urutan');
            $table->text('deskripsi_langkah');
            $table->boolean('wajib')->default(1)->nullable();
            $table->integer('poin')->default(0)->nullable();
            $table->bigInteger('deadline_waktu')->nullable();
            $table->bigInteger('toleransi_waktu_sebelum')->nullable();
            $table->bigInteger('toleransi_waktu_sesudah')->nullable();
            $table->boolean('wa_reminder')->default(0);
            $table->string('wa_jam_kirim', 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_sop_langkah');
    }
};
