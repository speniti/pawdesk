<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('services');
    }

    public function up(): void
    {
        Schema::create('services', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');
            $table->string('coat')->nullable();
            $table->integer('duration_minutes');
            $table->unsignedInteger('base_price')->comment('Price in cents');
            $table->boolean('combinable')->default(true);
            $table->string('status')->default('active');
            $table->json('size_prices')->default('{}');
            $table->timestamp('created_at', precision: 0)->useCurrent();
            $table->timestamp('updated_at', precision: 0)->useCurrent();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'category']);
            $table->index(['tenant_id', 'coat']);
        });
    }
};
