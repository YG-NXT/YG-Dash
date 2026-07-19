import { useState, useMemo, useRef, useCallback, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useFlashMessages } from '@/hooks/useFlashMessages';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { ScrollArea } from '@/components/ui/scroll-area';
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { SearchInput } from "@/components/ui/search-input";
import { Badge } from "@/components/ui/badge";
import NoRecordsFound from '@/components/no-records-found';
import { ModulesIndexProps, Module } from './types';
import { getPackageFavicon, getPackageAlias } from '@/utils/helpers';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import {
    Package,
    Plus,
    Power,
    PowerOff,
    Eye,
    ExternalLink,
    Sparkles,
    Search,
    ArrowRight,
    X,
    Grid3X3,
    List,
    Upload,
    Settings2,
    Puzzle,
    Zap,
    Globe
} from "lucide-react";

interface AddOn {
    name: string;
    image: string;
    url: string;
}

interface Category {
    name: string;
    icon: string;
    description: string;
    add_ons: AddOn[];
}

const slugify = (name: string) => name.toLowerCase().replace(/[^a-z0-9]+/g, '-');

type ViewMode = 'grid' | 'list';
type TabType = 'installed' | 'explore';

export default function Index() {
    const { modules, auth, addOns = [], exploreUrl = '', systemVersion = '' } = usePage<ModulesIndexProps & { addOns: Category[], exploreUrl: string, systemVersion: string }>().props;
    const { t } = useTranslation();

    const [searchTerm, setSearchTerm] = useState('');
    const [selectedModule, setSelectedModule] = useState<Module | null>(null);
    const [isDetailsOpen, setIsDetailsOpen] = useState(false);
    const [activeCategory, setActiveCategory] = useState<string>(addOns[0]?.name ?? '');
    const [exploreSearch, setExploreSearch] = useState('');
    const [viewMode, setViewMode] = useState<ViewMode>('grid');
    const [activeTab, setActiveTab] = useState<TabType>('installed');
    const sidebarRef = useRef<HTMLDivElement>(null);
    const sectionRefs = useRef<Record<string, HTMLElement | null>>({});
    const isScrollingTo = useRef(false);
    const scrollContainerRef = useRef<HTMLDivElement>(null);

    useFlashMessages();

    const filteredModules = modules.filter(module =>
        module.display !== false &&
        (module.alias.toLowerCase().includes(searchTerm.toLowerCase()) ||
        module.description.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    const installedCount = modules.filter(m => m.is_enabled).length;
    const totalCount = modules.length;

    const filteredAddOns = useMemo(() => {
        if (!exploreSearch.trim()) return null;
        const q = exploreSearch.toLowerCase();
        return addOns
            .map(cat => ({ ...cat, add_ons: cat.add_ons.filter(a => a.name.toLowerCase().includes(q)) }))
            .filter(cat => cat.add_ons.length > 0);
    }, [exploreSearch, addOns]);

    const updateActive = useCallback(() => {
        if (isScrollingTo.current) return;
        const container = scrollContainerRef.current?.querySelector<HTMLDivElement>('[data-radix-scroll-area-viewport]');
        if (!container) return;
        const scrollTop = container.scrollTop + container.clientHeight * 0.3;
        let current = addOns[0]?.name ?? '';
        for (const category of addOns) {
            const el = sectionRefs.current[category.name];
            if (el && el.offsetTop <= scrollTop) {
                current = category.name;
            }
        }
        setActiveCategory(current);
    }, [addOns]);

    useEffect(() => {
        const container = scrollContainerRef.current?.querySelector<HTMLDivElement>('[data-radix-scroll-area-viewport]');
        if (!container) return;
        container.addEventListener('scroll', updateActive, { passive: true });
        updateActive();
        return () => container.removeEventListener('scroll', updateActive);
    }, [updateActive]);

    useEffect(() => {
        if (!sidebarRef.current) return;
        const activeBtn = sidebarRef.current.querySelector<HTMLElement>('[data-active="true"]');
        if (activeBtn) {
            activeBtn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }
    }, [activeCategory]);

    const scrollToCategory = (name: string) => {
        const el = sectionRefs.current[name];
        const container = scrollContainerRef.current?.querySelector<HTMLDivElement>('[data-radix-scroll-area-viewport]');
        if (!el || !container) return;
        isScrollingTo.current = true;
        setActiveCategory(name);
        container.scrollTo({ top: el.offsetTop - 16, behavior: 'smooth' });
        setTimeout(() => { isScrollingTo.current = false; }, 800);
    };

    const displayCategories = filteredAddOns ?? addOns;

    const handleToggleModule = (moduleName: string, isEnabled: boolean) => {
        router.post(route('add-on.enable', moduleName), {}, {
            preserveState: true,
        });
    };

    const handleViewDetails = (module: Module) => {
        setSelectedModule(module);
        setIsDetailsOpen(true);
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[{label: t('Add-ons Manager')}]}
            pageTitle={t('Add-ons Manager')}
            pageActions={
                <div className="flex items-center gap-2">
                    {auth.user?.permissions?.includes('manage-add-on') && (
                        <>
                            <Button variant="outline" size="sm" onClick={() => router.visit(route('add-on.upload'))}>
                                <Upload className="h-4 w-4 mr-2" />
                                {t('Upload')}
                            </Button>
                            <Button size="sm" onClick={() => router.visit(route('add-on.upload'))}>
                                <Plus className="h-4 w-4 mr-2" />
                                {t('Install Add-on')}
                            </Button>
                        </>
                    )}
                </div>
            }
        >
            <Head title={t('Add-ons')} />

            {/* Stats Row */}
            <div className="grid gap-4 md:grid-cols-4 mb-6">
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <div className="p-2.5 bg-blue-50 rounded-lg">
                            <Puzzle className="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">{t('Total Modules')}</p>
                            <p className="text-xl font-bold text-gray-900">{totalCount}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <div className="p-2.5 bg-green-50 rounded-lg">
                            <Zap className="h-5 w-5 text-green-600" />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">{t('Active')}</p>
                            <p className="text-xl font-bold text-gray-900">{installedCount}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <div className="p-2.5 bg-orange-50 rounded-lg">
                            <PowerOff className="h-5 w-5 text-orange-600" />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">{t('Inactive')}</p>
                            <p className="text-xl font-bold text-gray-900">{totalCount - installedCount}</p>
                        </div>
                    </CardContent>
                </Card>
                <Card>
                    <CardContent className="p-4 flex items-center gap-4">
                        <div className="p-2.5 bg-purple-50 rounded-lg">
                            <Globe className="h-5 w-5 text-purple-600" />
                        </div>
                        <div>
                            <p className="text-sm text-gray-500">{t('Available Online')}</p>
                            <p className="text-xl font-bold text-gray-900">{addOns.length}</p>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <Tabs value={activeTab} onValueChange={(val) => setActiveTab(val as TabType)} className="space-y-6">
                <div className="flex items-center justify-between">
                    <TabsList>
                        <TabsTrigger value="installed" className="gap-2">
                            <Settings2 className="h-4 w-4" />
                            {t('Installed Modules')}
                        </TabsTrigger>
                        <TabsTrigger value="explore" className="gap-2">
                            <Globe className="h-4 w-4" />
                            {t('Explore Add-ons')}
                        </TabsTrigger>
                    </TabsList>
                    <div className="flex items-center gap-2">
                        {activeTab === 'installed' && (
                            <div className="flex items-center border rounded-lg p-0.5">
                                <Button
                                    variant={viewMode === 'grid' ? 'default' : 'ghost'}
                                    size="sm"
                                    onClick={() => setViewMode('grid')}
                                    className="h-8 w-8 p-0"
                                >
                                    <Grid3X3 className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant={viewMode === 'list' ? 'default' : 'ghost'}
                                    size="sm"
                                    onClick={() => setViewMode('list')}
                                    className="h-8 w-8 p-0"
                                >
                                    <List className="h-4 w-4" />
                                </Button>
                            </div>
                        )}
                    </div>
                </div>

                <TabsContent value="installed" className="space-y-6 mt-0">
                    <Card>
                        <CardHeader className="pb-4">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-base">{t('Installed Modules')}</CardTitle>
                                {systemVersion && (
                                    <Badge variant="outline" className="font-mono">
                                        {t('Version')}: v{systemVersion}
                                    </Badge>
                                )}
                            </div>
                            <SearchInput
                                value={searchTerm}
                                onChange={setSearchTerm}
                                onSearch={() => {}}
                                placeholder={t('Search installed modules...')}
                                className="w-full mt-4"
                            />
                        </CardHeader>
                        <CardContent>
                            {filteredModules.length > 0 ? (
                                viewMode === 'grid' ? (
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                                        {filteredModules.map((module) => (
                                            <Card key={module.name} className="group hover:shadow-md transition-all duration-200 border border-gray-200 flex flex-col">
                                                <div className="p-4 flex-1 flex flex-col">
                                                    <div className="flex items-start justify-between mb-3">
                                                        <div className="flex items-center gap-3">
                                                            <div className="relative p-2 bg-gray-50 rounded-lg">
                                                                <img
                                                                    src={getPackageFavicon(module.name)}
                                                                    alt={getPackageAlias(module.name)}
                                                                    className="h-8 w-8 object-contain"
                                                                    onError={(e) => {
                                                                        const target = e.target as HTMLImageElement;
                                                                        target.style.display = 'none';
                                                                        target.nextElementSibling?.classList.remove('hidden');
                                                                    }}
                                                                />
                                                                <Package className="h-8 w-8 text-gray-400 hidden absolute inset-0 m-auto" />
                                                            </div>
                                                            <div>
                                                                <span className="text-xs text-gray-500 font-mono">v{parseFloat(module.version).toFixed(1)}</span>
                                                            </div>
                                                        </div>
                                                        <Badge variant={module.is_enabled ? "default" : "secondary"} className="text-xs">
                                                            {module.is_enabled ? t('Active') : t('Inactive')}
                                                        </Badge>
                                                    </div>

                                                    <div className="mb-4 flex-1">
                                                        <h3 className="font-semibold text-gray-900 text-sm mb-1 line-clamp-2">{module.alias}</h3>
                                                        <p className="text-xs text-gray-500 line-clamp-2">{module.description}</p>
                                                    </div>

                                                    <div className="flex gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => handleViewDetails(module)}
                                                            className="flex-1 h-8 text-xs"
                                                        >
                                                            <Eye className="mr-1 h-3 w-3" />
                                                            {t('Details')}
                                                        </Button>
                                                        {auth.user?.permissions?.includes('manage-actions') && (
                                                            <TooltipProvider>
                                                                <Tooltip delayDuration={0}>
                                                                    <TooltipTrigger asChild>
                                                                        <Button
                                                                            variant="outline"
                                                                            size="sm"
                                                                            onClick={() => handleToggleModule(module.name, module.is_enabled)}
                                                                            className={`h-8 w-8 p-0 \${module.is_enabled ? 'bg-red-50 hover:bg-red-100 border-red-200' : 'bg-green-50 hover:bg-green-100 border-green-200'}`}
                                                                        >
                                                                            {module.is_enabled ? (
                                                                                <PowerOff className="h-3 w-3 text-red-600" />
                                                                            ) : (
                                                                                <Power className="h-3 w-3 text-green-600" />
                                                                            )}
                                                                        </Button>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <p>{module.is_enabled ? t('Disable') : t('Enable')}</p>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </TooltipProvider>
                                                        )}
                                                    </div>
                                                </div>
                                            </Card>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="space-y-2">
                                        {filteredModules.map((module) => (
                                            <Card key={module.name} className="hover:shadow-sm transition-all border border-gray-200">
                                                <CardContent className="p-4 flex items-center justify-between">
                                                    <div className="flex items-center gap-4">
                                                        <div className="relative p-2 bg-gray-50 rounded-lg">
                                                            <img
                                                                src={getPackageFavicon(module.name)}
                                                                alt={getPackageAlias(module.name)}
                                                                className="h-8 w-8 object-contain"
                                                                onError={(e) => {
                                                                    const target = e.target as HTMLImageElement;
                                                                    target.style.display = 'none';
                                                                    target.nextElementSibling?.classList.remove('hidden');
                                                                }}
                                                            />
                                                            <Package className="h-8 w-8 text-gray-400 hidden absolute inset-0 m-auto" />
                                                        </div>
                                                        <div>
                                                            <h3 className="font-medium text-gray-900 text-sm">{module.alias}</h3>
                                                            <p className="text-xs text-gray-500 line-clamp-1">{module.description}</p>
                                                        </div>
                                                    </div>
                                                    <div className="flex items-center gap-3">
                                                        <span className="text-xs text-gray-500 font-mono">v{parseFloat(module.version).toFixed(1)}</span>
                                                        <Badge variant={module.is_enabled ? "default" : "secondary"} className="text-xs">
                                                            {module.is_enabled ? t('Active') : t('Inactive')}
                                                        </Badge>
                                                        <div className="flex gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleViewDetails(module)}
                                                                className="h-8 w-8 p-0"
                                                            >
                                                                <Eye className="h-3.5 w-3.5" />
                                                            </Button>
                                                            {auth.user?.permissions?.includes('manage-actions') && (
                                                                <TooltipProvider>
                                                                    <Tooltip delayDuration={0}>
                                                                        <TooltipTrigger asChild>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => handleToggleModule(module.name, module.is_enabled)}
                                                                                className={`h-8 w-8 p-0 \${module.is_enabled ? 'text-red-600 hover:text-red-700' : 'text-green-600 hover:text-green-700'}`}
                                                                            >
                                                                                {module.is_enabled ? (
                                                                                    <PowerOff className="h-3.5 w-3.5" />
                                                                                ) : (
                                                                                    <Power className="h-3.5 w-3.5" />
                                                                                )}
                                                                            </Button>
                                                                        </TooltipTrigger>
                                                                        <TooltipContent>
                                                                            <p>{module.is_enabled ? t('Disable') : t('Enable')}</p>
                                                                        </TooltipContent>
                                                                    </Tooltip>
                                                                </TooltipProvider>
                                                            )}
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        ))}
                                    </div>
                                )
                            ) : (
                                <NoRecordsFound
                                    icon={Package}
                                    title={t('No modules found')}
                                    description={searchTerm ? t('No modules match your search criteria.') : t('No modules are available.')}
                                    hasFilters={!!searchTerm}
                                    onClearFilters={() => setSearchTerm('')}
                                />
                            )}
                        </CardContent>
                    </Card>
                </TabsContent>

                <TabsContent value="explore" className="mt-0">
                    {addOns.length > 0 ? (
                        <div className="flex gap-6">
                            <div className="w-56 shrink-0 hidden lg:block">
                                <Card className="sticky top-4 border border-gray-100 shadow-sm">
                                    <CardContent className="p-3">
                                        <div className="relative mb-3">
                                            <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" />
                                            <input
                                                type="text"
                                                value={exploreSearch}
                                                onChange={e => setExploreSearch(e.target.value)}
                                                placeholder={t('Search...')}
                                                className="w-full pl-8 pr-3 py-1.5 text-xs border border-gray-200 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary transition-all"
                                            />
                                        </div>
                                        <ScrollArea className="max-h-[calc(100vh-200px)]" ref={sidebarRef}>
                                            <ul className="space-y-0.5">
                                                {addOns.map(category => {
                                                    const isActive = activeCategory === category.name && !exploreSearch;
                                                    return (
                                                        <li key={category.name}>
                                                            <button
                                                                data-active={isActive}
                                                                onClick={() => { setExploreSearch(''); scrollToCategory(category.name); }}
                                                                className={`w-full flex items-center justify-between gap-2 px-3 py-2.5 rounded-lg text-sm text-left transition-all duration-150 \${isActive ? 'bg-primary text-primary-foreground shadow-sm' : 'hover:bg-gray-50 text-gray-600 hover:text-gray-900'}`}
                                                            >
                                                                <span className="flex items-center gap-2.5 truncate">
                                                                    <i className={`\${category.icon} text-base shrink-0`} />
                                                                    <span className="truncate font-medium">{category.name}</span>
                                                                </span>
                                                                <span className={`shrink-0 text-xs font-semibold px-1.5 py-0.5 rounded-md \${isActive ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-500'}`}>
                                                                    {category.add_ons.length}
                                                                </span>
                                                            </button>
                                                        </li>
                                                    );
                                                })}
                                            </ul>
                                        </ScrollArea>
                                    </CardContent>
                                </Card>
                            </div>

                            <ScrollArea className="flex-1 min-w-0 h-[calc(100vh-280px)]" ref={scrollContainerRef}>
                                <div className="space-y-8 pr-4">
                                    {exploreSearch && filteredAddOns?.length === 0 && (
                                        <div className="flex flex-col items-center justify-center py-16 text-center">
                                            <Search className="h-10 w-10 text-gray-300 mb-3" />
                                            <p className="font-medium text-gray-500">{t('No add-ons match')} \"{exploreSearch}\"</p>
                                            <div className="flex items-center gap-2 mt-3">
                                                <button
                                                    onClick={() => setExploreSearch('')}
                                                    className="inline-flex items-center gap-1.5 text-sm font-semibold border border-gray-200 text-gray-600 px-4 py-2 rounded-lg hover:bg-gray-50 transition-colors"
                                                >
                                                    <X className="h-3.5 w-3.5" />
                                                    {t('Clear Search')}
                                                </button>
                                                <a
                                                    href={exploreUrl}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1.5 text-sm font-semibold bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors"
                                                >
                                                    {t('Explore All Add-ons')} <ArrowRight className="h-3.5 w-3.5" />
                                                </a>
                                            </div>
                                        </div>
                                    )}

                                    {displayCategories.map(category => (
                                        <section
                                            key={category.name}
                                            id={slugify(category.name)}
                                            ref={el => { sectionRefs.current[category.name] = el; }}
                                        >
                                            <div className="flex items-center gap-3 mb-4">
                                                <div className="p-2 bg-primary/10 rounded-lg">
                                                    <i className={`\${category.icon} text-xl text-primary`} />
                                                </div>
                                                <div>
                                                    <h3 className="font-bold text-gray-900 text-base">{category.name}</h3>
                                                    <p className="text-xs text-gray-400">{category.add_ons.length} {t('add-ons')}</p>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                                {category.add_ons.map(addon => (
                                                    <a
                                                        key={addon.name}
                                                        href={addon.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="group"
                                                    >
                                                        <Card className="h-full border border-gray-200 hover:border-primary hover:shadow-md transition-all duration-200 overflow-hidden">
                                                            <div className="h-1 w-full bg-gradient-to-r from-primary/0 via-primary/50 to-primary/0 opacity-0 group-hover:opacity-100 transition-opacity" />
                                                            <div className="p-4 flex flex-col items-center text-center gap-3">
                                                                {addon.image ? (
                                                                    <img
                                                                        src={addon.image}
                                                                        alt={addon.name}
                                                                        className="w-12 h-12 object-contain rounded-lg"
                                                                        onError={(e) => {
                                                                            const el = e.target as HTMLImageElement;
                                                                            el.style.display = 'none';
                                                                            el.nextElementSibling?.classList.remove('hidden');
                                                                        }}
                                                                    />
                                                                ) : null}
                                                                <Package className={`h-10 w-10 text-primary/40 \${addon.image ? 'hidden' : ''}`} />
                                                                <p className="text-sm font-semibold text-gray-800 line-clamp-2 leading-snug">
                                                                    {addon.name}
                                                                </p>
                                                                <span className="inline-flex items-center gap-1 text-xs font-semibold text-primary/70 group-hover:text-primary transition-colors">
                                                                    {t('View Details')} <ExternalLink className="h-2.5 w-2.5" />
                                                                </span>
                                                            </div>
                                                        </Card>
                                                    </a>
                                                ))}
                                            </div>
                                        </section>
                                    ))}

                                    {!exploreSearch && (
                                        <div className="rounded-xl border border-dashed border-primary/30 bg-primary/5 p-5 flex items-center justify-between gap-4">
                                            <div>
                                                <p className="font-semibold text-gray-800 text-sm">{t("Can't find what you need?")}</p>
                                                <p className="text-xs text-gray-500 mt-0.5">{t('Browse our full marketplace with 300+ add-ons across all categories.')}</p>
                                            </div>
                                            <a
                                                href={exploreUrl}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="shrink-0 inline-flex items-center gap-2 bg-primary text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-primary/90 transition-colors"
                                            >
                                                {t('View All')} <ArrowRight className="h-3.5 w-3.5" />
                                            </a>
                                        </div>
                                    )}
                                </div>
                            </ScrollArea>
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="py-12 text-center">
                                <Globe className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                                <p className="text-gray-500">{t('No add-ons available in the marketplace')}</p>
                            </CardContent>
                        </Card>
                    )}
                </TabsContent>
            </Tabs>

            <Dialog open={isDetailsOpen} onOpenChange={setIsDetailsOpen}>
                <DialogContent className="max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-3">
                            <div className="p-2 bg-gray-50 rounded-lg">
                                <img
                                    src={selectedModule?.image}
                                    alt={selectedModule?.alias}
                                    className="h-8 w-8 object-contain rounded"
                                    onError={(e) => {
                                        const target = e.target as HTMLImageElement;
                                        target.style.display = 'none';
                                        target.nextElementSibling?.classList.remove('hidden');
                                    }}
                                />
                                <Package className="h-8 w-8 text-gray-400 hidden" />
                            </div>
                            {selectedModule?.alias}
                        </DialogTitle>
                        <DialogDescription>
                            {selectedModule?.description}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span className="font-medium text-gray-600">{t('Version')}:</span>
                                <p className="text-green-600 font-medium">v{selectedModule?.version}</p>
                            </div>
                            <div>
                                <span className="font-medium text-gray-600">{t('Status')}:</span>
                                <p className={`font-medium \${selectedModule?.is_enabled ? 'text-green-600' : 'text-gray-500'}`}>
                                    {selectedModule?.is_enabled ? t('Active') : t('Inactive')}
                                </p>
                            </div>
                        </div>
                        {selectedModule?.package_name && (
                            <div>
                                <span className="font-medium text-gray-600">{t('Package')}:</span>
                                <p className="text-sm text-gray-800 font-mono">{selectedModule.package_name}</p>
                            </div>
                        )}
                        {auth.user?.permissions?.includes('manage-actions') && (
                            <div className="flex gap-2 pt-4 border-t">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => {
                                        if (selectedModule) {
                                            handleToggleModule(selectedModule.name, selectedModule.is_enabled);
                                            setIsDetailsOpen(false);
                                        }
                                    }}
                                    className={`flex-1 \${selectedModule?.is_enabled ? 'bg-red-50 hover:bg-red-100 border-red-200 text-red-600' : 'bg-green-50 hover:bg-green-100 border-green-200 text-green-600'}`}
                                >
                                    {selectedModule?.is_enabled ? (
                                        <>
                                            <PowerOff className="mr-2 h-4 w-4" />
                                            {t('Disable')}
                                        </>
                                    ) : (
                                        <>
                                            <Power className="mr-2 h-4 w-4" />
                                            {t('Enable')}
                                        </>
                                    )}
                                </Button>
                            </div>
                        )}
                    </div>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
