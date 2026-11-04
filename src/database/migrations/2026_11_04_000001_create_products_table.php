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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->string('normalized_url', 2048);
            $table->string('domain', 100);
            $table->string('title')->nullable();
            $table->string('nickname', 120)->nullable();
            $table->string('category', 50);
            $table->string('image_url', 2048)->nullable();
            $table->decimal('target_price', 10, 2);
            $table->decimal('current_price', 10, 2)->nullable();
            $table->decimal('installment_price', 10, 2)->nullable();
            $table->boolean('in_stock')->default(true);
            $table->boolean('notify_on_stock')->default(false);
            $table->string('status', 30)->default('pending');
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_manual_check_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'normalized_url']);
            $table->index(['user_id', 'category', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
