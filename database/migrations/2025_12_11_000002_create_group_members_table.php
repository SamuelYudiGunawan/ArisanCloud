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
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->uuid('group_id');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('join_date')->useCurrent();
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('arisan_groups')->onDelete('cascade');
            $table->unique(['group_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};

