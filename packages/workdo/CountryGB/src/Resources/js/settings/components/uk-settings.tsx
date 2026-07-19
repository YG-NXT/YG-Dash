import { useState, useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { CheckCircle2, XCircle, Building2, FileText, Users } from 'lucide-react';

interface UKSettingsProps {
    userSettings?: Record<string, string>;
    auth?: any;
}

interface UKSettingsData {
    company_number: string;
    utr: string;
    paye_reference: string;
    accounts_office_reference: string;
    cis_contractor_number: string;
    vat_scheme: string;
    fiscal_year_end: string;
    vat_registered: boolean;
    cis_registered: boolean;
    paye_registered: boolean;
}

export default function UKSettings({ userSettings, auth }: UKSettingsProps) {
    const { t } = useTranslation();
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [flash, setFlash] = useState<{ success?: string; error?: string }>({});

    const [formData, setFormData] = useState<UKSettingsData>({
        company_number: userSettings?.company_number || userSettings?.registration_number || '',
        utr: userSettings?.utr || '',
        paye_reference: userSettings?.paye_reference || '',
        accounts_office_reference: userSettings?.accounts_office_reference || '',
        cis_contractor_number: userSettings?.cis_contractor_number || '',
        vat_scheme: userSettings?.vat_scheme || 'standard',
        fiscal_year_end: userSettings?.fiscal_year_end || '03-31',
        vat_registered: userSettings?.vat_registered || false,
        cis_registered: userSettings?.cis_registered || false,
        paye_registered: userSettings?.paye_registered || false,
    });

    useEffect(() => {
        if (userSettings) {
            setFormData({
                company_number: userSettings?.company_number || userSettings?.registration_number || '',
                utr: userSettings?.utr || '',
                paye_reference: userSettings?.paye_reference || '',
                accounts_office_reference: userSettings?.accounts_office_reference || '',
                cis_contractor_number: userSettings?.cis_contractor_number || '',
                vat_scheme: userSettings?.vat_scheme || 'standard',
                fiscal_year_end: userSettings?.fiscal_year_end || '03-31',
                vat_registered: userSettings?.vat_registered || false,
                cis_registered: userSettings?.cis_registered || false,
                paye_registered: userSettings?.paye_registered || false,
            });
        }
    }, [userSettings]);

    const handleChange = (field: keyof UKSettingsData, value: string | boolean) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setFlash({});

        router.post('/uk/settings', formData, {
            onSuccess: () => {
                setFlash({ success: 'UK settings saved successfully' });
                setIsSubmitting(false);
            },
            onError: (errors) => {
                setFlash({ error: 'Failed to save settings' });
                setIsSubmitting(false);
            },
        });
    };

    return (
        <div id="uk-settings" className="space-y-6">
            <div className="flex items-center gap-2 mb-6">
                <Building2 className="h-6 w-6" />
                <div>
                    <h2 className="text-2xl font-bold">{t('UK Settings')}</h2>
                    <p className="text-sm text-gray-500">UK-specific tax, payroll, and compliance configuration</p>
                </div>
            </div>

            {flash.success && (
                <Alert className="border-green-200 bg-green-50">
                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                    <AlertTitle className="text-green-800">Success</AlertTitle>
                    <AlertDescription className="text-green-700">{flash.success}</AlertDescription>
                </Alert>
            )}

            {flash.error && (
                <Alert className="border-red-200 bg-red-50">
                    <XCircle className="h-4 w-4 text-red-600" />
                    <AlertTitle className="text-red-800">Error</AlertTitle>
                    <AlertDescription className="text-red-700">{flash.error}</AlertDescription>
                </Alert>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            Company Registration
                        </CardTitle>
                        <CardDescription>
                            Companies House and HMRC identification details
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="company_number">Companies House Number</Label>
                                <Input
                                    id="company_number"
                                    value={formData.company_number}
                                    onChange={(e) => handleChange('company_number', e.target.value)}
                                    placeholder="AB123456"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="utr">Unique Taxpayer Reference (UTR)</Label>
                                <Input
                                    id="utr"
                                    value={formData.utr}
                                    onChange={(e) => handleChange('utr', e.target.value)}
                                    placeholder="1234567890"
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            VAT Configuration
                        </CardTitle>
                        <CardDescription>
                            VAT registration and scheme settings
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="vat_registered"
                                checked={formData.vat_registered}
                                onChange={(e) => handleChange('vat_registered', e.target.checked)}
                                className="h-4 w-4"
                            />
                            <Label htmlFor="vat_registered" className="cursor-pointer">
                                VAT Registered
                            </Label>
                        </div>

                        {formData.vat_registered && (
                            <div className="space-y-2">
                                <Label htmlFor="vat_scheme">VAT Scheme</Label>
                                <select
                                    id="vat_scheme"
                                    value={formData.vat_scheme}
                                    onChange={(e) => handleChange('vat_scheme', e.target.value)}
                                    className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option value="standard">Standard VAT</option>
                                    <option value="flat_rate">Flat Rate Scheme</option>
                                    <option value="cash_scheme">Cash Accounting Scheme</option>
                                    <option value="annual">Annual Accounting Scheme</option>
                                </select>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            PAYE & Payroll
                        </CardTitle>
                        <CardDescription>
                            PAYE registration and payroll configuration
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="paye_registered"
                                checked={formData.paye_registered}
                                onChange={(e) => handleChange('paye_registered', e.target.checked)}
                                className="h-4 w-4"
                            />
                            <Label htmlFor="paye_registered" className="cursor-pointer">
                                PAYE Registered
                            </Label>
                        </div>

                        {formData.paye_registered && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="paye_reference">PAYE Reference</Label>
                                    <Input
                                        id="paye_reference"
                                        value={formData.paye_reference}
                                        onChange={(e) => handleChange('paye_reference', e.target.value)}
                                        placeholder="123/AB123456"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="accounts_office_reference">Accounts Office Reference</Label>
                                    <Input
                                        id="accounts_office_reference"
                                        value={formData.accounts_office_reference}
                                        onChange={(e) => handleChange('accounts_office_reference', e.target.value)}
                                        placeholder="123AB12345678"
                                    />
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            CIS
                        </CardTitle>
                        <CardDescription>
                            Construction Industry Scheme registration
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="cis_registered"
                                checked={formData.cis_registered}
                                onChange={(e) => handleChange('cis_registered', e.target.checked)}
                                className="h-4 w-4"
                            />
                            <Label htmlFor="cis_registered" className="cursor-pointer">
                                CIS Registered Contractor
                            </Label>
                        </div>

                        {formData.cis_registered && (
                            <div className="space-y-2">
                                <Label htmlFor="cis_contractor_number">CIS Contractor Number</Label>
                                <Input
                                    id="cis_contractor_number"
                                    value={formData.cis_contractor_number}
                                    onChange={(e) => handleChange('cis_contractor_number', e.target.value)}
                                    placeholder="CIS Contractor Number"
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Fiscal Year
                        </CardTitle>
                        <CardDescription>
                            UK tax year end date
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="fiscal_year_end">Fiscal Year End</Label>
                            <select
                                id="fiscal_year_end"
                                value={formData.fiscal_year_end}
                                onChange={(e) => handleChange('fiscal_year_end', e.target.value)}
                                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option value="01-31">31 January</option>
                                <option value="02-28">28/29 February</option>
                                <option value="03-31">31 March</option>
                                <option value="04-30">30 April</option>
                                <option value="05-31">31 May</option>
                                <option value="06-30">30 June</option>
                                <option value="07-31">31 July</option>
                                <option value="08-31">31 August</option>
                                <option value="09-30">30 September</option>
                                <option value="10-31">31 October</option>
                                <option value="11-30">30 November</option>
                                <option value="12-31">31 December</option>
                            </select>
                        </div>
                    </CardContent>
                </Card>

                <div className="flex justify-end">
                    <Button type="submit" disabled={isSubmitting}>
                        {isSubmitting ? 'Saving...' : 'Save UK Settings'}
                    </Button>
                </div>
            </form>
        </div>
    );
}
