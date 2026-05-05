<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }

    public function up(): void
    {
        Schema::create('appointments', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('requested');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->text('internal_notes')->nullable();
            $table->timestamp('created_at', precision: 0)->useCurrent();
            $table->timestamp('updated_at', precision: 0)->useCurrent();

            $table->index(['tenant_id', 'start_time', 'end_time']);
            $table->index(['tenant_id', 'customer_id']);
            $table->index(['tenant_id', 'user_id']);
        });
    }
};
