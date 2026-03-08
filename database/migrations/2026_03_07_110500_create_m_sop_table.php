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
        if (!Schema::hasTable('m_sop')) {
            Schema::create('m_sop', function (Blueprint $table) {
                $table->id();
                $table->foreignId('katsop_id')->constrained('m_kat_sop')->onDelete('restrict');
                $table->string('kode', 20)->unique();
                $table->string('nama');
                $table->text('deskripsi')->nullable();
                $table->string('dokumen')->nullable();
                $table->string('versi', 10)->default('1.0');
                $table->date('tanggal_berlaku')->nullable();
                $table->date('tanggal_kadaluarsa')->nullable();
                $table->enum('status', ['aktif', 'nonaktif'])->default('aktif');
                $table->enum('status_sop', ['draft', 'review', 'approved'])->default('draft');
                $table->foreignId('pengawas_id')->nullable()->constrained('m_user')->onDelete('set null');
                $table->integer('total_poin')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_sop');
    }
};
