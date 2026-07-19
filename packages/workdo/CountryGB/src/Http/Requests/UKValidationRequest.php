<?php

namespace Workdo\CountryGB\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UKValidationRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'field' => 'required|string|in:postcode,vat_number,company_number,phone,utr,nino,cis_number,sort_code,account_number',
            'value' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'field.required' => 'Field is required',
            'field.in' => 'Invalid validation field',
            'value.required' => 'Value is required',
        ];
    }
}
