<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Restores the structure only: historical consent timestamps are not
     * restored, as the column is recreated empty.
     */
    public function down(): void
    {
        Schema::table('customers', static function (Blueprint $table) {
            $table->timestamp('marketing_consent_at')->nullable()->after('gdpr_policy_sent_at');
            $table->index(['tenant_id', 'marketing_consent_at']);
        });
    }

    public function up(): void
    {
        Schema::table('customers', static function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'marketing_consent_at']);
            $table->dropColumn('marketing_consent_at');
        });
    }
};
