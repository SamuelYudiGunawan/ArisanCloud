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
        Schema::create('payment_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('group_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('period_id');
            $table->integer('amount_paid');
            $table->timestamp('payment_date')->useCurrent();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('proof_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('arisan_groups')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('arisan_periods')->onDelete('cascade');
            $table->unique(['group_id', 'user_id', 'period_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_history');
    }
};

