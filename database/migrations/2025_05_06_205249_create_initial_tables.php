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
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->decimal('base_amount', 15, 2);

            $table->timestamps();

            $table->unique(['user_id', 'year', 'month']);
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('tx_date');
            $table->enum('type', ['expense', 'income']);
            $table->decimal('amount', 15, 2);
            $table->string('description')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'tx_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists("transactions");
        Schema::dropIfExists("budgets");
    }
};
