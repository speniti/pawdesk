<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('treatments');
    }

    public function up(): void
    {
        Schema::create('treatments', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->integer('actual_duration_minutes');
            $table->unsignedInteger('final_price')->comment('Price in cents');
            $table->text('notes')->nullable();
            $table->boolean('visible_to_customer')->default(true);
            $table->text('products_used')->nullable();
            $table->timestamp('created_at', precision: 0)->useCurrent();
            $table->timestamp('updated_at', precision: 0)->useCurrent();

            $table->index(['tenant_id', 'pet_id', 'created_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'customer_id']);
        });
    }
};
