<?php

namespace Workdo\CountryGB\Database\Seeders;

use Spatie\Permission\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionTableSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            ['name' => 'manage-uk-settings', 'module' => 'country-gb', 'label' => 'Manage UK Settings'],
            ['name' => 'view-uk-settings', 'module' => 'country-gb', 'label' => 'View UK Settings'],
            ['name' => 'manage-hmrc', 'module' => 'country-gb', 'label' => 'Manage HMRC Integration'],
            ['name' => 'submit-vat-return', 'module' => 'country-gb', 'label' => 'Submit VAT Return'],
            ['name' => 'submit-rti', 'module' => 'country-gb', 'label' => 'Submit RTI Payroll'],
            ['name' => 'view-vat-return', 'module' => 'country-gb', 'label' => 'View VAT Return'],
            ['name' => 'view-cis-return', 'module' => 'country-gb', 'label' => 'View CIS Return'],
            ['name' => 'view-paye-summary', 'module' => 'country-gb', 'label' => 'View PAYE Summary'],
            ['name' => 'manage-companies-house', 'module' => 'country-gb', 'label' => 'Manage Companies House'],
            ['name' => 'manage-nhs', 'module' => 'country-gb', 'label' => 'Manage NHS Integration'],
            ['name' => 'manage-cqc', 'module' => 'country-gb', 'label' => 'Manage CQC Integration'],
            ['name' => 'manage-fsa', 'module' => 'country-gb', 'label' => 'Manage FSA Integration'],
            ['name' => 'manage-uk-payroll', 'module' => 'country-gb', 'label' => 'Manage UK Payroll'],
            ['name' => 'generate-uk-reports', 'module' => 'country-gb', 'label' => 'Generate UK Reports'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission['name']],
                [
                    'add_on' => 'country-gb',
                    'module' => $permission['module'],
                    'label' => $permission['label']
                ]
            );
        }
    }
}
