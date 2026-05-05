<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('pets');
    }

    public function up(): void
    {
        Schema::create('pets', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('species');
            $table->string('breed')->nullable();
            $table->string('sex')->default('unknown');
            $table->date('date_of_birth')->nullable();
            $table->string('size');
            $table->string('coat')->nullable();
            $table->text('behavioral_notes')->nullable();
            $table->text('health_notes')->nullable();
            $table->timestamp('created_at', precision: 0)->useCurrent();
            $table->timestamp('updated_at', precision: 0)->useCurrent();

            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'name']);
        });
    }
};
