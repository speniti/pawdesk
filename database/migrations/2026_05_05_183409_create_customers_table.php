<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }

    public function up(): void
    {
        Schema::create('customers', static function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->string('address')->nullable();
            $table->string('preferred_channel')->default('email');
            $table->timestamp('gdpr_policy_sent_at')->nullable();
            $table->timestamp('marketing_consent_at')->nullable();
            $table->json('preferences')->default('{}');
            $table->text('notes')->nullable();
            $table->timestamp('created_at', precision: 0)->useCurrent();
            $table->timestamp('updated_at', precision: 0)->useCurrent();

            $table->unique(['tenant_id', 'email']);
            $table->index(['tenant_id', 'email']);
            $table->index(['tenant_id', 'first_name']);
            $table->index(['tenant_id', 'last_name']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'marketing_consent_at']);
        });
    }
};
