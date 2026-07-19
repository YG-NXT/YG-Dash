import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Separator } from '@/components/ui/separator';
import { 
    Save, Eye, Settings2, ArrowUpDown, Palette, Layout, Image, Star, 
    Layers, Zap, Monitor, CreditCard, AlignLeft, Package, CheckCircle,
    Globe, ChevronRight
} from 'lucide-react';
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { toast } from 'sonner';

import General from './components/settings/General';
import Hero from './components/settings/Hero';
import Header from './components/settings/Header';
import Features from './components/settings/Features';
import Stats from './components/settings/Stats';
import Modules from './components/settings/Modules';
import Benefits from './components/settings/Benefits';
import Gallery from './components/settings/Gallery';
import CTA from './components/settings/CTA';
import Footer from './components/settings/Footer';
import Order from './components/settings/Order';
import Colors from './components/settings/Colors';
import Addon from './components/settings/Addon';
import Pricing from './components/settings/Pricing';
import { LandingPreview } from './components/LandingPreview';

interface LandingPageSetting {
    id?: number;
    company_name?: string;
    contact_email?: string;
    contact_phone?: string;
    contact_address?: string;
    default_country?: string;
    config_sections?: any;
}

interface CustomPage {
    id: number;
    title: string;
    slug: string;
}

interface Country {
    code: string;
    name: string;
    flag: string;
}

interface SettingsProps {
    settings: LandingPageSetting;
    customPages: CustomPage[];
    availableCountries?: Country[];
}

const SETUP_ITEMS = [
    { key: 'general', label: 'General', icon: Settings2, description: 'Company information & contact details' },
    { key: 'order', label: 'Section Order', icon: ArrowUpDown, description: 'Arrange landing page sections' },
];

const LAYOUT_ITEMS = [
    { key: 'header', label: 'Header', icon: AlignLeft, description: 'Logo, navigation & top bar' },
    { key: 'hero', label: 'Hero Section', icon: Layout, description: 'Main banner & headline' },
    { key: 'footer', label: 'Footer', icon: Layers, description: 'Footer links & copyright' },
];

const CONTENT_ITEMS = [
    { key: 'features', label: 'Features', icon: Star, description: 'Key features showcase' },
    { key: 'modules', label: 'Modules', icon: Monitor, description: 'Available modules display' },
    { key: 'benefits', label: 'Benefits', icon: CheckCircle, description: 'Benefits & advantages' },
];

const SOCIAL_ITEMS = [
    { key: 'stats', label: 'Statistics', icon: Layers, description: 'Stats & numbers' },
    { key: 'gallery', label: 'Gallery', icon: Image, description: 'Image gallery & portfolio' },
];

const ENGAGEMENT_ITEMS = [
    { key: 'cta', label: 'Call to Action', icon: Zap, description: 'CTA buttons & sections' },
];

const THEME_ITEMS = [
    { key: 'colors', label: 'Colors & Theme', icon: Palette, description: 'Primary, secondary & accent colors' },
];

const PAGE_ITEMS = [
    { key: 'addon', label: 'Add-ons', icon: Package, description: 'Add-ons page settings' },
    { key: 'pricing', label: 'Pricing', icon: CreditCard, description: 'Pricing plans display' },
];

const ALL_SECTIONS = [
    ...SETUP_ITEMS,
    ...LAYOUT_ITEMS,
    ...CONTENT_ITEMS,
    ...SOCIAL_ITEMS,
    ...ENGAGEMENT_ITEMS,
    ...THEME_ITEMS,
    ...PAGE_ITEMS,
];

export default function Settings({ settings, customPages, availableCountries = [] }: SettingsProps) {
    const { t } = useTranslation();
    const { auth } = usePage<{auth: {user: any}}>().props;

    if (!auth.user?.permissions?.includes('manage-landing-page')) {
        return (
            <AuthenticatedLayout
                breadcrumbs={[{ label: t('Landing Page Settings') }]}
                pageTitle={t('Landing Page Settings')}
            >
                <Head title={t('Landing Page Settings')} />
                <div className="text-center py-12">
                    <p className="text-gray-500">{t('You do not have permission to access this page.')}</p>
                </div>
            </AuthenticatedLayout>
        );
    }

    const [activeTab, setActiveTab] = useState<'setup' | 'layout' | 'content' | 'social' | 'engagement' | 'themecolor' | 'page'>('setup');
    const [activeSection, setActiveSection] = useState<string>('general');

    const { data, setData, post, processing } = useForm({
        company_name: settings.company_name || '',
        contact_email: settings.contact_email || '',
        contact_phone: settings.contact_phone || '',
        contact_address: settings.contact_address || '',
        default_country: settings.default_country || 'US',
        config_sections: settings.config_sections || {
            sections: {},
            section_visibility: {
                header: true, hero: true, stats: true, features: true,
                modules: true, benefits: true, gallery: true, cta: true,
                footer: true, addons: true, pricing: true
            },
            section_order: ['header', 'hero', 'stats', 'features', 'modules', 'benefits', 'gallery', 'cta', 'footer']
        }
    });

    const getSectionData = (key: string) => data.config_sections?.sections?.[key] || {};

    const updateSectionData = (key: string, updates: any) => {
        const currentSections = { ...data.config_sections?.sections };
        currentSections[key] = { ...currentSections[key], ...updates };
        setData('config_sections', { ...data.config_sections, sections: currentSections });
    };

    const updateSectionVisibility = (sectionKey: string, visible: boolean) => {
        setData('config_sections', {
            ...data.config_sections,
            section_visibility: { ...data.config_sections?.section_visibility, [sectionKey]: visible }
        });
    };

    const saveSettings = () => {
        post(route('landing-page.store'), {
            preserveScroll: true,
            onSuccess: (page) => {
                if (page.props.flash?.success) toast.success(page.props.flash.success);
            },
            onError: (errors) => {
                toast.error(errors.message || t('Failed to save settings'));
            }
        });
    };

    const getSectionsForTab = () => {
        switch (activeTab) {
            case 'setup': return SETUP_ITEMS;
            case 'layout': return LAYOUT_ITEMS;
            case 'content': return CONTENT_ITEMS;
            case 'social': return SOCIAL_ITEMS;
            case 'engagement': return ENGAGEMENT_ITEMS;
            case 'themecolor': return THEME_ITEMS;
            case 'page': return PAGE_ITEMS;
            default: return [];
        }
    };

    const renderSectionContent = () => {
        switch (activeSection) {
            case 'general': return <General data={data} updateSectionData={(field, value) => setData(field, value)} availableCountries={availableCountries} />;
            case 'hero': return <Hero data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'features': return <Features data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'header': return <Header data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} customPages={customPages || []} />;
            case 'stats': return <Stats data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'modules': return <Modules data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'benefits': return <Benefits data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'gallery': return <Gallery data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'cta': return <CTA data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'footer': return <Footer data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} customPages={customPages || []} />;
            case 'order': return <Order data={data} setData={setData} updateSectionVisibility={updateSectionVisibility} />;
            case 'colors': return <Colors data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} setData={setData} />;
            case 'addon': return <Addon data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            case 'pricing': return <Pricing data={data} getSectionData={getSectionData} updateSectionData={updateSectionData} updateSectionVisibility={updateSectionVisibility} />;
            default: return null;
        }
    };

    const getTabLabel = (tab: string) => {
        const labels: Record<string, string> = {
            setup: t('Setup'),
            layout: t('Layout'),
            content: t('Content'),
            social: t('Social'),
            engagement: t('Engagement'),
            themecolor: t('Theme Color'),
            page: t('Page'),
        };
        return labels[tab] || tab;
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: t('Landing Page Settings') }]}
            pageTitle={t('Landing Page Settings')}
            pageActions={
                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => window.open(route('landing.page'), '_blank')}>
                        <Eye className="h-4 w-4 mr-2" />
                        {t('View Landing Page')}
                    </Button>
                    {auth.user?.permissions?.includes('edit-landing-page') && (
                        <Button
                            onClick={saveSettings}
                            disabled={processing}
                            size="sm"
                            className="text-white"
                            style={{ backgroundColor: 'hsl(var(--primary))' }}
                        >
                            <Save className="h-4 w-4 mr-2" />
                            {processing ? t('Saving...') : t('Save Changes')}
                        </Button>
                    )}
                </div>
            }
        >
            <Head title={t('Landing Page Settings')} />

            <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                {/* Left Sidebar - Odoo Style */}
                <div className="lg:col-span-3">
                    <Card className="border-0 shadow-sm">
                        <CardContent className="p-0">
                            <ScrollArea className="h-[calc(100vh-200px)]">
                                <div className="p-2">
                                    {(['setup', 'layout', 'content', 'social', 'engagement', 'themecolor', 'page'] as const).map((tab) => {
                                        const items = getSectionsForTab();
                                        const isActive = activeTab === tab;
                                        return (
                                            <div key={tab} className="mb-1">
                                                <button
                                                    onClick={() => {
                                                        setActiveTab(tab);
                                                        const firstItem = getSectionsForTab()[0];
                                                        if (firstItem) setActiveSection(firstItem.key);
                                                    }}
                                                    className={`w-full flex items-center justify-between px-3 py-2.5 rounded-lg text-sm font-medium transition-all ${
                                                        isActive
                                                            ? 'bg-primary text-primary-foreground shadow-sm'
                                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                                    }`}
                                                >
                                                    <span>{getTabLabel(tab)}</span>
                                                    <ChevronRight className={`h-4 w-4 transition-transform ${isActive ? 'rotate-90' : ''}`} />
                                                </button>
                                                {isActive && (
                                                    <div className="mt-1 ml-2 pl-3 border-l-2 border-primary/20 space-y-0.5">
                                                        {items.map((item) => {
                                                            const Icon = item.icon;
                                                            const isSectionActive = activeSection === item.key;
                                                            return (
                                                                <button
                                                                    key={item.key}
                                                                    onClick={() => setActiveSection(item.key)}
                                                                    className={`w-full flex items-start gap-3 px-3 py-2.5 rounded-lg text-left transition-all ${
                                                                        isSectionActive
                                                                            ? 'bg-primary/10 text-primary'
                                                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                                                                    }`}
                                                                >
                                                                    <Icon className="h-4 w-4 mt-0.5 shrink-0" />
                                                                    <div className="min-w-0">
                                                                        <div className="text-sm font-medium truncate">{t(item.label)}</div>
                                                                        <div className="text-xs text-gray-400 truncate hidden lg:block">{t(item.description)}</div>
                                                                    </div>
                                                                </button>
                                                            );
                                                        })}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            </ScrollArea>
                        </CardContent>
                    </Card>
                </div>

                {/* Main Content Area */}
                <div className="lg:col-span-6 space-y-6">
                    {/* Section Header */}
                    <div>
                        <h2 className="text-xl font-semibold text-gray-900">
                            {ALL_SECTIONS.find(s => s.key === activeSection)?.label || activeSection}
                        </h2>
                        <p className="text-sm text-gray-500 mt-1">
                            {ALL_SECTIONS.find(s => s.key === activeSection)?.description || ''}
                        </p>
                    </div>

                    {/* Section Content */}
                    <div>
                        {renderSectionContent()}
                    </div>
                </div>

                {/* Right Preview Panel */}
                <div className="lg:col-span-3">
                    <div className="sticky top-6">
                        <Card>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-base flex items-center gap-2">
                                    <Eye className="h-4 w-4" />
                                    {t('Live Preview')}
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="p-4">
                                <LandingPreview settings={data} />
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}