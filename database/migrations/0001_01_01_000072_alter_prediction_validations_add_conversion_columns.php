<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prediction_validations', function (Blueprint $table) {
            $table->integer('conversion_count')->nullable()->after('metrics_snapshot');
            $table->decimal('conversion_value', 12, 2)->nullable()->after('conversion_count');
        });
    }

    public function down(): void
    {
        Schema::table('prediction_validations', function (Blueprint $table) {
            $table->dropColumn(['conversion_count', 'conversion_value']);
        });
    }
};
