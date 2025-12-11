<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add current_cycle to arisan_groups
        Schema::table('arisan_groups', function (Blueprint $table) {
            $table->integer('current_cycle')->default(1)->after('contribution_amount');
        });

        // Add cycle_number to draw_history
        Schema::table('draw_history', function (Blueprint $table) {
            $table->integer('cycle_number')->default(1)->after('total_pot_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('arisan_groups', function (Blueprint $table) {
            $table->dropColumn('current_cycle');
        });

        Schema::table('draw_history', function (Blueprint $table) {
            $table->dropColumn('cycle_number');
        });
    }
};

