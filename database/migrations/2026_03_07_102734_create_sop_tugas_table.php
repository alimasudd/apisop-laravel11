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
        if (!Schema::hasTable('m_sop_tugas')) {
            Schema::create('m_sop_tugas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sop_id')->comment('FK ke m_sop');
                $table->unsignedBigInteger('sop_langkah_id')->nullable()->comment('FK ke m_sop_langkah (NULL = semua langkah di SOP ini)');
                $table->unsignedBigInteger('user_id')->comment('FK ke m_user (karyawan yang ditugaskan)');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sop_tugas');
    }
};
