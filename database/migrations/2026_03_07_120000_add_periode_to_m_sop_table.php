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
        if (Schema::hasTable('m_sop') && !Schema::hasColumn('m_sop', 'periode')) {
            Schema::table('m_sop', function (Blueprint $table) {
                $table->string('periode')->nullable()->after('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('m_sop') && Schema::hasColumn('m_sop', 'periode')) {
            Schema::table('m_sop', function (Blueprint $table) {
                $table->dropColumn('periode');
            });
        }
    }
};
