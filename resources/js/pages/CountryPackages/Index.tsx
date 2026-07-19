import { Head } from '@inertiajs/react';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from "@/components/ui/card";
import { Button } from '@/components/ui/button';
import { Badge } from "@/components/ui/badge";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from "@/components/ui/switch";
import { useTranslation } from 'react-i18next';
import { 
    Globe, 
    Package, 
    Users, 
    TrendingUp, 
    MapPin, 
    CheckCircle2, 
    XCircle, 
    Settings,
    ArrowUpRight,
    Search
} from "lucide-react";
import { useState } from 'react';

interface Package {
    name: string;
    alias: string;
    country_code: string | null;
    description: string;
    version: string;
    package_name: string;
    is_active: boolean;
    company_count: number;
    flag: string;
}

interface Props {
    packages: Package[];
    totalCountries: number;
    activeCountries: number;
    totalCompanies: number;
}

export default function CountryPackages({ packages, totalCountries, activeCountries, totalCompanies }: Props) {
    const { t } = useTranslation();
    const [searchTerm, setSearchTerm] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const filteredPackages = packages.filter(pkg => {
        const matchesSearch = !searchTerm || 
            pkg.alias.toLowerCase().includes(searchTerm.toLowerCase()) ||
            pkg.country_code?.toLowerCase().includes(searchTerm.toLowerCase()) ||
            pkg.description.toLowerCase().includes(searchTerm.toLowerCase());
        
        const matchesStatus = statusFilter === 'all' || 
            (statusFilter === 'active' && pkg.is_active) ||
            (statusFilter === 'inactive' && !pkg.is_active);

        return matchesSearch && matchesStatus;
    });

    const stats = [
        {
            title: 'Total Countries',
            value: totalCountries,
            icon: Globe,
            color: 'text-blue-600',
            bg: 'bg-blue-50',
        },
        {
            title: 'Active Countries',
            value: activeCountries,
            icon: CheckCircle2,
            color: 'text-green-600',
            bg: 'bg-green-50',
        },
        {
            title: 'Total Companies',
            value: totalCompanies,
            icon: Users,
            color: 'text-purple-600',
            bg: 'bg-purple-50',
        },
        {
            title: 'Coverage',
            value: totalCountries > 0 ? Math.round((activeCountries / totalCountries) * 100) : 0,
            icon: TrendingUp,
            color: 'text-orange-600',
            bg: 'bg-orange-50',
            suffix: '%',
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Country Packages') }]}
            pageTitle={t('Country Packages')}
        >
            <Head title={t('Country Packages')} />

            <div className="space-y-6">
                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {stats.map((stat) => (
                        <Card key={stat.title}>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm font-medium text-gray-600">{t(stat.title)}</p>
                                        <p className="text-2xl font-bold text-gray-900">
                                            {stat.value}{stat.suffix || ''}
                                        </p>
                                    </div>
                                    <div className={`p-3 rounded-full ${stat.bg}`}>
                                        <stat.icon className={`h-6 w-6 ${stat.color}`} />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="p-6">
                        <div className="flex flex-col md:flex-row gap-4">
                            <div className="flex-1">
                                <Label htmlFor="search">{t('Search')}</Label>
                                <div className="relative">
                                    <Search className="absolute start-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" />
                                    <Input
                                        id="search"
                                        placeholder={t("Search countries...")}
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="ps-10"
                                    />
                                </div>
                            </div>
                            <div>
                                <Label htmlFor="status">{t('Status')}</Label>
                                <Select value={statusFilter} onValueChange={setStatusFilter}>
                                    <SelectTrigger className="w-40">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">{t('All')}</SelectItem>
                                        <SelectItem value="active">{t('Active')}</SelectItem>
                                        <SelectItem value="inactive">{t('Inactive')}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Country Packages Grid */}
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {filteredPackages.map((pkg) => (
                        <Card key={pkg.name} className="hover:shadow-lg transition-shadow">
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <span className="text-4xl">{pkg.flag}</span>
                                        <div>
                                            <CardTitle className="text-lg">{pkg.alias}</CardTitle>
                                            <CardDescription>{pkg.country_code}</CardDescription>
                                        </div>
                                    </div>
                                    <Badge variant={pkg.is_active ? "default" : "secondary"}>
                                        {pkg.is_active ? t('Active') : t('Inactive')}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <p className="text-sm text-gray-600 line-clamp-2">{pkg.description}</p>
                                
                                <div className="flex items-center gap-4 text-sm text-gray-500">
                                    <div className="flex items-center gap-1">
                                        <Users className="h-4 w-4" />
                                        <span>{pkg.company_count} {t('Companies')}</span>
                                    </div>
                                    <div className="flex items-center gap-1">
                                        <Package className="h-4 w-4" />
                                        <span>v{pkg.version}</span>
                                    </div>
                                </div>

                                <div className="flex items-center justify-between pt-4 border-t">
                                    <div className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 text-gray-400" />
                                        <span className="text-sm font-medium">{pkg.country_code}</span>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant={pkg.is_active ? "outline" : "default"}
                                        disabled={pkg.is_active}
                                    >
                                        {pkg.is_active ? (
                                            <>
                                                <CheckCircle2 className="h-4 w-4 me-2" />
                                                {t('Active')}
                                            </>
                                        ) : (
                                            <>
                                                <Settings className="h-4 w-4 me-2" />
                                                {t('Activate')}
                                            </>
                                        )}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {filteredPackages.length === 0 && (
                    <Card>
                        <CardContent className="p-12 text-center">
                            <Globe className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">{t('No country packages found')}</h3>
                            <p className="text-gray-500">{t('Install a country package to get started')}</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
