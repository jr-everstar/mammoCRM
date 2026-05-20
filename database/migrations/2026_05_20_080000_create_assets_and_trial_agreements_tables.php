<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag')->unique();
            $table->string('type')->index();
            $table->string('side')->nullable()->index();
            $table->string('serial_number')->nullable()->index();
            $table->string('model_name')->nullable();
            $table->string('status')->default('available')->index();
            $table->string('condition')->default('good');
            $table->string('location')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trial_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_user_id')->constrained('users');
            $table->foreignId('account_manager_id')->constrained('users');
            $table->foreignId('generated_by')->constrained('users');
            $table->foreignId('signed_uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('document_number')->unique();
            $table->string('status')->default('generated')->index();
            $table->date('effective_date');
            $table->date('trial_start_date');
            $table->date('trial_end_date');
            $table->string('trial_location')->nullable();
            $table->string('client_coordinator_name')->nullable();
            $table->string('client_coordinator_title')->nullable();
            $table->string('client_coordinator_email')->nullable();
            $table->string('client_coordinator_phone')->nullable();
            $table->string('delivery_method')->nullable();
            $table->string('return_method')->nullable();
            $table->string('return_address')->nullable();
            $table->string('trial_fee')->default('Waived');
            $table->string('security_deposit')->default('N/A');
            $table->text('special_conditions')->nullable();
            $table->string('everstar_address')->nullable();
            $table->string('director_name')->nullable();
            $table->string('generated_docx_path')->nullable();
            $table->string('generated_pdf_path')->nullable();
            $table->string('signed_pdf_path')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('signed_uploaded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('trial_agreement_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trial_agreement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('role');
            $table->string('condition_at_handover')->default('good');
            $table->string('condition_at_return')->nullable();
            $table->timestamps();
            $table->unique(['trial_agreement_id', 'asset_id']);
        });

        Schema::create('trial_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('value')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('account_documents', function (Blueprint $table) {
            $table->string('generated_file_path')->nullable()->after('notes');
            $table->string('signed_file_path')->nullable()->after('generated_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('account_documents', function (Blueprint $table) {
            $table->dropColumn(['generated_file_path', 'signed_file_path']);
        });

        Schema::dropIfExists('trial_agreement_assets');
        Schema::dropIfExists('trial_agreements');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('trial_settings');
    }
};
