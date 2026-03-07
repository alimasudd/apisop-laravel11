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
        if (!Schema::hasTable('m_sop_pelaksana')) {
            Schema::create('m_sop_pelaksana', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('area_id')->nullable()->comment('FK ke m_area');
                $table->unsignedBigInteger('sop_id')->comment('FK ke m_sop');
                $table->unsignedBigInteger('sop_langkah_id')->nullable()->comment('FK ke m_sop_langkah');
                $table->unsignedBigInteger('ruang_id')->nullable()->comment('FK ke m_ruang');
                $table->tinyInteger('status_sop')->nullable()->comment('0=Harian, 1=Mingguan, 2=Bulanan, 3=Tahunan');
                $table->unsignedBigInteger('user_id')->comment('FK ke m_user (pelaksana)');
                $table->integer('poin')->default(0);
                $table->text('des')->nullable()->comment('Deskripsi/Catatan pelaksanaan');
                $table->string('url')->nullable()->comment('URL bukti Foto/Video');
                $table->bigInteger('deadline_waktu')->nullable();
                $table->bigInteger('toleransi_waktu_sebelum')->nullable();
                $table->bigInteger('toleransi_waktu_sesudah')->nullable();
                $table->bigInteger('waktu_mulai')->nullable();
                $table->bigInteger('waktu_selesai')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_pelaksanas');
    }
};
