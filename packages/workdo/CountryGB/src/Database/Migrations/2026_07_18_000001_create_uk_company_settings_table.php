<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uk_company_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('country_code', 2)->default('GB');
            $table->string('vat_number')->nullable();
            $table->string('company_number')->nullable();
            $table->string('utr')->nullable();
            $table->string('paye_reference')->nullable();
            $table->string('accounts_office_reference')->nullable();
            $table->string('cis_contractor_number')->nullable();
            $table->string('vat_scheme')->nullable(); // standard, flat_rate, cash_scheme, annual
            $table->string('fiscal_year_end')->default('03-31'); // MM-DD
            $table->string('hmrc_client_id')->nullable();
            $table->string('hmrc_client_secret')->nullable();
            $table->string('hmrc_access_token')->nullable();
            $table->string('hmrc_refresh_token')->nullable();
            $table->timestamp('hmrc_token_expires_at')->nullable();
            $table->string('companies_house_api_key')->nullable();
            $table->string('nhs_api_key')->nullable();
            $table->string('cqc_api_key')->nullable();
            $table->boolean('vat_registered')->default(false);
            $table->boolean('cis_registered')->default(false);
            $table->boolean('paye_registered')->default(false);
            $table->timestamps();

            $table->unique('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uk_company_settings');
    }
};
