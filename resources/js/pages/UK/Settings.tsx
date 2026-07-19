import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { CheckCircle2, XCircle, Info, Building2, FileText, CreditCard, Users, Settings2 } from "lucide-react";
import { useEffect } from 'react';

interface UKSettings {
    id: number;
    vat_number: string | null;
    company_number: string | null;
    utr: string | null;
    paye_reference: string | null;
    accounts_office_reference: string | null;
    cis_contractor_number: string | null;
    vat_scheme: string | null;
    fiscal_year_end: string;
    hmrc_client_id: string | null;
    hmrc_client_secret: string | null;
    hmrc_access_token: string | null;
    companies_house_api_key: string | null;
    nhs_api_key: string | null;
    cqc_api_key: string | null;
    vat_registered: boolean;
    cis_registered: boolean;
    paye_registered: boolean;
}

interface OnboardingStep {
    title: string;
    fields: Record<string, {
        label: string;
        type: string;
        required: boolean;
        options?: string[];
        default?: string;
    }>;
}

interface RequiredIntegration {
    name: string;
    description: string;
    required_for_vat_registered?: boolean;
    required_for_employers?: boolean;
    required_for_limited_companies?: boolean;
}

interface RecommendedGateway {
    name: string;
    description: string;
    use_cases: string[];
}

export default function UKSettings() {
    const { t } = useTranslation();
    const { settings, onboardingSteps, requiredIntegrations, recommendedGateways, flash } = usePage().props as any;
    
    const [formData, setFormData] = useState<UKSettings>(settings || {
        id: 0,
        vat_number: '',
        company_number: '',
        utr: '',
        paye_reference: '',
        accounts_office_reference: '',
        cis_contractor_number: '',
        vat_scheme: 'standard',
        fiscal_year_end: '03-31',
        hmrc_client_id: '',
        hmrc_client_secret: '',
        hmrc_access_token: '',
        companies_house_api_key: '',
        nhs_api_key: '',
        cqc_api_key: '',
        vat_registered: false,
        cis_registered: false,
        paye_registered: false,
    });

    const [activeTab, setActiveTab] = useState('company');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleChange = (field: keyof UKSettings, value: string | boolean) => {
        setFormData(prev => ({ ...prev, [field]: value }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        
        router.post('/uk/settings', formData, {
            onSuccess: () => setIsSubmitting(false),
            onError: () => setIsSubmitting(false),
        });
    };

    const hmrcConnected = formData.hmrc_access_token && formData.hmrc_refresh_token;

    return (
        <AuthenticatedLayout>
            <Head title={t("UK Settings")} />
            
            <div className="container mx-auto py-6">
                <div className="flex items-center gap-2 mb-6">
                    <Building2 className="h-6 w-6" />
                    <h1 className="text-2xl font-bold">{t("UK Settings")}</h1>
                </div>

                {flash?.success && (
                    <Alert className="mb-6 border-green-200 bg-green-50">
                        <CheckCircle2 className="h-4 w-4 text-green-600" />
                        <AlertTitle className="text-green-800">Success</AlertTitle>
                        <AlertDescription className="text-green-700">{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash?.error && (
                    <Alert className="mb-6 border-red-200 bg-red-50">
                        <XCircle className="h-4 w-4 text-red-600" />
                        <AlertTitle className="text-red-800">Error</AlertTitle>
                        <AlertDescription className="text-red-700">{flash.error}</AlertDescription>
                    </Alert>
                )}

                <form onSubmit={handleSubmit}>
                    <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
                        <TabsList className="grid w-full grid-cols-5">
                            <TabsTrigger value="company" className="flex items-center gap-2">
                                <Building2 className="h-4 w-4" />
                                {t("Company Details")}
                            </TabsTrigger>
                            <TabsTrigger value="tax" className="flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                {t("Tax Details")}
                            </TabsTrigger>
                            <TabsTrigger value="payroll" className="flex items-center gap-2">
                                <Users className="h-4 w-4" />
                                {t("Payroll Details")}
                            </TabsTrigger>
                            <TabsTrigger value="integrations" className="flex items-center gap-2">
                                <Settings2 className="h-4 w-4" />
                                {t("Integrations")}
                            </TabsTrigger>
                            <TabsTrigger value="payment" className="flex items-center gap-2">
                                <CreditCard className="h-4 w-4" />
                                {t("Payment Gateways")}
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="company">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t("Company Details")}</CardTitle>
                                    <CardDescription>
                                        Your company registration and identification details
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="vat_number">VAT Number</Label>
                                            <Input
                                                id="vat_number"
                                                value={formData.vat_number || ''}
                                                onChange={(e) => handleChange('vat_number', e.target.value)}
                                                placeholder="GB123456789"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="company_number">Companies House Number</Label>
                                            <Input
                                                id="company_number"
                                                value={formData.company_number || ''}
                                                onChange={(e) => handleChange('company_number', e.target.value)}
                                                placeholder="AB123456"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="utr">Unique Taxpayer Reference (UTR)</Label>
                                            <Input
                                                id="utr"
                                                value={formData.utr || ''}
                                                onChange={(e) => handleChange('utr', e.target.value)}
                                                placeholder="1234567890"
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="fiscal_year_end">Fiscal Year End</Label>
                                            <Select
                                                value={formData.fiscal_year_end || '03-31'}
                                                onValueChange={(value) => handleChange('fiscal_year_end', value)}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="01-31">31 January</SelectItem>
                                                    <SelectItem value="02-28">28/29 February</SelectItem>
                                                    <SelectItem value="03-31">31 March</SelectItem>
                                                    <SelectItem value="04-30">30 April</SelectItem>
                                                    <SelectItem value="05-31">31 May</SelectItem>
                                                    <SelectItem value="06-30">30 June</SelectItem>
                                                    <SelectItem value="07-31">31 July</SelectItem>
                                                    <SelectItem value="08-31">31 August</SelectItem>
                                                    <SelectItem value="09-30">30 September</SelectItem>
                                                    <SelectItem value="10-31">31 October</SelectItem>
                                                    <SelectItem value="11-30">30 November</SelectItem>
                                                    <SelectItem value="12-31">31 December</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="tax">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t("Tax Details")}</CardTitle>
                                    <CardDescription>
                                        VAT registration and tax scheme configuration
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
                                            {t("Are you VAT registered?")}
                                        </Label>
                                    </div>

                                    {formData.vat_registered && (
                                        <div className="space-y-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="vat_scheme">VAT Scheme</Label>
                                                <Select
                                                    value={formData.vat_scheme || 'standard'}
                                                    onValueChange={(value) => handleChange('vat_scheme', value)}
                                                >
                                                    <SelectTrigger>
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="standard">Standard Accounting</SelectItem>
                                                        <SelectItem value="flat_rate">Flat Rate Scheme</SelectItem>
                                                        <SelectItem value="cash_scheme">Cash Accounting Scheme</SelectItem>
                                                        <SelectItem value="annual">Annual Accounting Scheme</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                            </div>
                                        </div>
                                    )}

                                    <Alert>
                                        <Info className="h-4 w-4" />
                                        <AlertTitle>VAT Rates</AlertTitle>
                                        <AlertDescription>
                                            UK VAT rates: Standard 20%, Reduced 5%, Zero 0%, Exempt. Reverse charge applies to B2B services between VAT-registered businesses.
                                        </AlertDescription>
                                    </Alert>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="payroll">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t("Payroll Details")}</CardTitle>
                                    <CardDescription>
                                        PAYE, CIS, and payroll configuration
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
                                            {t("PAYE Registered")}
                                        </Label>
                                    </div>

                                    {formData.paye_registered && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="paye_reference">PAYE Reference</Label>
                                                <Input
                                                    id="paye_reference"
                                                    value={formData.paye_reference || ''}
                                                    onChange={(e) => handleChange('paye_reference', e.target.value)}
                                                    placeholder="123/AB123456"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="accounts_office_reference">Accounts Office Reference</Label>
                                                <Input
                                                    id="accounts_office_reference"
                                                    value={formData.accounts_office_reference || ''}
                                                    onChange={(e) => handleChange('accounts_office_reference', e.target.value)}
                                                    placeholder="123/AB123456"
                                                />
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            id="cis_registered"
                                            checked={formData.cis_registered}
                                            onChange={(e) => handleChange('cis_registered', e.target.checked)}
                                            className="h-4 w-4"
                                        />
                                        <Label htmlFor="cis_registered" className="cursor-pointer">
                                            {t("CIS Registered")}
                                        </Label>
                                    </div>

                                    {formData.cis_registered && (
                                        <div className="space-y-2">
                                            <Label htmlFor="cis_contractor_number">CIS Contractor Number</Label>
                                            <Input
                                                id="cis_contractor_number"
                                                value={formData.cis_contractor_number || ''}
                                                onChange={(e) => handleChange('cis_contractor_number', e.target.value)}
                                                placeholder="123456789A"
                                            />
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <TabsContent value="integrations">
                            <div className="space-y-6">
                                <Card>
                                    <CardHeader>
                                        <CardTitle>HMRC (Making Tax Digital)</CardTitle>
                                        <CardDescription>
                                            Connect to HMRC for VAT returns and payroll submissions
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="space-y-2">
                                                <Label htmlFor="hmrc_client_id">HMRC Client ID</Label>
                                                <Input
                                                    id="hmrc_client_id"
                                                    value={formData.hmrc_client_id || ''}
                                                    onChange={(e) => handleChange('hmrc_client_id', e.target.value)}
                                                    placeholder="Your HMRC application client ID"
                                                />
                                            </div>
                                            <div className="space-y-2">
                                                <Label htmlFor="hmrc_client_secret">HMRC Client Secret</Label>
                                                <Input
                                                    id="hmrc_client_secret"
                                                    type="password"
                                                    value={formData.hmrc_client_secret || ''}
                                                    onChange={(e) => handleChange('hmrc_client_secret', e.target.value)}
                                                    placeholder="Your HMRC application client secret"
                                                />
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-4">
                                            {hmrcConnected ? (
                                                <Alert className="border-green-200 bg-green-50">
                                                    <CheckCircle2 className="h-4 w-4 text-green-600" />
                                                    <AlertTitle className="text-green-800">Connected to HMRC</AlertTitle>
                                                    <AlertDescription className="text-green-700">
                                                        Your HMRC integration is active. Token expires: {formData.hmrc_token_expires_at ? new Date(formData.hmrc_token_expires_at).toLocaleString() : 'Unknown'}
                                                    </AlertDescription>
                                                </Alert>
                                            ) : (
                                                <Alert className="border-yellow-200 bg-yellow-50">
                                                    <Info className="h-4 w-4 text-yellow-600" />
                                                    <AlertTitle className="text-yellow-800">Not Connected</AlertTitle>
                                                    <AlertDescription className="text-yellow-700">
                                                        Enter your HMRC application credentials and save to connect.
                                                    </AlertDescription>
                                                </Alert>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Companies House</CardTitle>
                                        <CardDescription>
                                            API key for company lookups and filings
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            <Label htmlFor="companies_house_api_key">Companies House API Key</Label>
                                            <Input
                                                id="companies_house_api_key"
                                                value={formData.companies_house_api_key || ''}
                                                onChange={(e) => handleChange('companies_house_api_key', e.target.value)}
                                                placeholder="Your Companies House API key"
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>NHS Digital</CardTitle>
                                        <CardDescription>
                                            API key for NHS Spine integration (healthcare)
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            <Label htmlFor="nhs_api_key">NHS API Key</Label>
                                            <Input
                                                id="nhs_api_key"
                                                value={formData.nhs_api_key || ''}
                                                onChange={(e) => handleChange('nhs_api_key', e.target.value)}
                                                placeholder="Your NHS Digital API key"
                                            />
                                        </div>
                                    </CardContent>
                                </Card>

                                <Card>
                                    <CardHeader>
                                        <CardTitle>Care Quality Commission (CQC)</CardTitle>
                                        <CardDescription>
                                            API key for CQC provider lookups (healthcare)
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-2">
                                            <Label htmlFor="cqc_api_key">CQC API Key</Label>
                                            <Input
                                                id="cqc_api_key"
                                                value={formData.cqc_api_key || ''}
                                                onChange={(e) => handleChange('cqc_api_key', e.target.value)}
                                                placeholder="Your CQC API key"
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </TabsContent>

                        <TabsContent value="payment">
                            <Card>
                                <CardHeader>
                                    <CardTitle>{t("Recommended Payment Gateways")}</CardTitle>
                                    <CardDescription>
                                        UK-specific payment methods and gateways
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        {recommendedGateways?.map((gateway: RecommendedGateway) => (
                                            <div key={gateway.name} className="border rounded-lg p-4">
                                                <h3 className="font-semibold">{gateway.name}</h3>
                                                <p className="text-sm text-gray-600 mt-1">{gateway.description}</p>
                                                <div className="flex gap-2 mt-2">
                                                    {gateway.use_cases?.map((useCase: string) => (
                                                        <span key={useCase} className="text-xs bg-gray-100 px-2 py-1 rounded">
                                                            {useCase}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>
                    </Tabs>

                    <div className="mt-6 flex justify-end">
                        <Button type="submit" disabled={isSubmitting}>
                            {isSubmitting ? 'Saving...' : t("Save Settings")}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}
