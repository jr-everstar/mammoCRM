<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_stage_rules', function (Blueprint $table) {
            $table->id();
            $table->string('stage')->unique();
            $table->string('label');
            $table->unsignedTinyInteger('probability');
            $table->text('guidance')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_stage_rules');
    }
};
