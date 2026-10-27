import { usePage } from '@inertiajs/react';
import { Folders, LayoutGrid, Package, ShieldCheck, Users } from 'lucide-react';
import { dashboard } from '@/routes';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { index as itemsIndex } from '@/routes/admin/items';
import { index as usersIndex } from '@/routes/admin/users';
import type { NavItem } from '@/types';

export function useAdminNavigation(): NavItem[] {
    const { auth } = usePage().props;
    const items: NavItem[] = [];
    if (!auth.permissions.includes('admin.access')) return items;
    if (auth.permissions.includes('dashboard.view'))
        items.push({ title: 'Dashboard', href: dashboard(), icon: LayoutGrid });
    if (auth.permissions.includes('users.view'))
        items.push({ title: 'Users', href: usersIndex(), icon: Users });
    if (auth.permissions.includes('roles.view'))
        items.push({
            title: 'Roles & Permissions',
            href: rolesIndex(),
            icon: ShieldCheck,
        });
    if (auth.permissions.includes('items.view'))
        items.push({ title: 'Items', href: itemsIndex(), icon: Package });
    if (auth.permissions.includes('categories.view'))
        items.push({
            title: 'Categories',
            href: categoriesIndex(),
            icon: Folders,
        });
    return items;
}
