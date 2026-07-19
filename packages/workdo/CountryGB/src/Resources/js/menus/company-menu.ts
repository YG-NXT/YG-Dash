import { NavItem } from '@/types';
import { Building2, FileText, Users, CreditCard, Settings2 } from 'lucide-react';

export const getCompanyMenu = (t: (key: string) => string): NavItem[] => {
    return [
        {
            name: 'UK Settings',
            title: t('UK Settings'),
            icon: Building2,
            href: '/uk/settings',
            permission: 'manage-uk-settings',
            order: 9999,
        },
    ];
};
