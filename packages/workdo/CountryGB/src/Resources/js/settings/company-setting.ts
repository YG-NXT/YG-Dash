import { Building2 } from 'lucide-react';
import { SettingMenuItem } from '@/utils/menus/company-setting';

export const getCompanySetting = (t: (key: string) => string): SettingMenuItem[] => [
    {
        order: 36,
        title: t('UK Settings'),
        href: '#uk-settings',
        icon: Building2,
        permission: 'manage-company-settings',
        component: 'uk-settings'
    }
];
