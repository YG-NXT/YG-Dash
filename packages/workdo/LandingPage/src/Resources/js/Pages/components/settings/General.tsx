import { useTranslation } from 'react-i18next';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { PhoneInputComponent } from '@/components/ui/phone-input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Building2, Globe } from 'lucide-react';

interface Country {
    code: string;
    name: string;
    flag: string;
}

interface GeneralProps {
    data: any;
    updateSectionData: (field: string, value: any) => void;
    availableCountries?: Country[];
}

export default function General({ data, updateSectionData, availableCountries = [] }: GeneralProps) {
    const { t } = useTranslation();

    return (
        <div className="space-y-6">
            <Card>
                <CardHeader>
                    <div className="flex items-center gap-3">
                        <div className="p-2 bg-blue-100 rounded-lg">
                            <Building2 className="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <CardTitle className="text-base">{t('Company Information')}</CardTitle>
                            <p className="text-sm text-gray-500">{t('Basic company details for your landing page')}</p>
                        </div>
                    </div>
                </CardHeader>
                <CardContent className="space-y-6">
                    <div>
                        <Label>{t('Company Name')}</Label>
                        <Input
                            value={data.company_name || ''}
                            onChange={(e) => updateSectionData('company_name', e.target.value)}
                            placeholder={t('Your Company Name')}
                        />
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <Label>{t('Contact Email')}</Label>
                            <Input
                                type="email"
                                value={data.contact_email || ''}
                                onChange={(e) => updateSectionData('contact_email', e.target.value)}
                                placeholder="support@company.com"
                            />
                        </div>
                        <div className="space-y-2">
                            <PhoneInputComponent
                                label={t('Contact Phone')}
                                value={data.contact_phone || ''}
                                onChange={(value) => updateSectionData('contact_phone', value)}
                                placeholder="+1 (555) 123-4567"
                            />
                        </div>
                    </div>

                    <div>
                        <Label>{t('Contact Address')}</Label>
                        <Textarea
                            value={data.contact_address || ''}
                            onChange={(e) => updateSectionData('contact_address', e.target.value)}
                            placeholder="123 Business Ave, City, State 12345"
                            rows={2}
                        />
                    </div>

                    {availableCountries.length > 0 && (
                        <div>
                            <Label>{t('Default Country')}</Label>
                            <Select
                                value={data.default_country || 'US'}
                                onValueChange={(value) => updateSectionData('default_country', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select default country')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableCountries.map((country) => (
                                        <SelectItem key={country.code} value={country.code}>
                                            <span className="flex items-center gap-2">
                                                <span>{country.flag}</span>
                                                <span>{country.name}</span>
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <p className="text-xs text-gray-500 mt-1.5">
                                {t('This country will be used for default landing page content and compliance badges')}
                            </p>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}