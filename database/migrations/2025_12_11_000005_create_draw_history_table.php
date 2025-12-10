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
        Schema::create('draw_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->uuid('period_id');
            $table->foreignId('winner_user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('draw_date')->useCurrent();
            $table->integer('total_pot_amount');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('arisan_groups')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('arisan_periods')->onDelete('cascade');
            $table->unique(['group_id', 'period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draw_history');
    }
};

