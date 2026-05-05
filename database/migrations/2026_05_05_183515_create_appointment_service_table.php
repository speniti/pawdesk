<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('appointment_service');
    }

    public function up(): void
    {
        Schema::create('appointment_service', static function (Blueprint $table) {
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('applied_price')->comment('Price in cents');
            $table->integer('duration_minutes');
            $table->timestamp('created_at', precision: 0)->useCurrent();

            $table->primary(['appointment_id', 'service_id']);
        });
    }
};
