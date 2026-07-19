<?php

namespace Workdo\CountryGB\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UKCompanySetting extends Model
{
    protected $fillable = [
        'created_by',
        'country_code',
        'vat_number',
        'company_number',
        'utr',
        'paye_reference',
        'accounts_office_reference',
        'cis_contractor_number',
        'vat_scheme',
        'fiscal_year_end',
        'hmrc_client_id',
        'hmrc_client_secret',
        'hmrc_access_token',
        'hmrc_refresh_token',
        'hmrc_token_expires_at',
        'companies_house_api_key',
        'nhs_api_key',
        'cqc_api_key',
        'vat_registered',
        'cis_registered',
        'paye_registered',
    ];

    protected $casts = [
        'vat_registered' => 'boolean',
        'cis_registered' => 'boolean',
        'paye_registered' => 'boolean',
        'hmrc_token_expires_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
