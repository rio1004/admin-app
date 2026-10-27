import { useTranslation } from '@/hooks/use-language';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Plus, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/admin/empty-state';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, destroy, edit, index } from '@/routes/admin/roles';

type Role = {
    id: number;
    name: string;
    permissions: string[];
    users_count: number;
    protected: boolean;
    can: { update: boolean; delete: boolean };
};
type Props = {
    roles: {
        data: Role[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search?: string; sort?: string; direction?: string };
    canCreate: boolean;
};

export default function RolesIndex({ roles, filters, canCreate }: Props) {
    const t = useTranslation();
    const search = useForm({
        search: filters.search ?? '',
        sort: filters.sort ?? 'name',
        direction: filters.direction ?? 'asc',
    });
    const deletion = useForm({});
    const [selected, setSelected] = useState<Role | null>(null);
    const { errors } = usePage().props;

    return (
        <>
            <Head title={t('Roles & Permissions')} />
            <PageContainer>
                <PageHeading
                    title={t('Roles & Permissions')}
                    description={t(
                        "Define what your team can access. Permissions come from the application's defined set.",
                    )}
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    {t('Create role')}
                                </Link>
                            </Button>
                        )
                    }
                />
                <p className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm leading-6 text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200">
                    {t(
                        'The Admin role is protected. Other roles can only grant permissions you hold. Roles with assigned users must be reassigned before deletion.',
                    )}
                </p>
                <Card className="min-w-0 shadow-none">
                    <CardContent className="space-y-6">
                        <form
                            aria-busy={search.processing}
                            onSubmit={(event) => {
                                event.preventDefault();
                                search.get(index.url(), {
                                    preserveState: true,
                                    preserveScroll: true,
                                });
                            }}
                            className="flex flex-wrap items-end gap-3"
                        >
                            <div className="min-w-40 flex-1">
                                <Label htmlFor="role-search">
                                    {t('Search roles')}
                                </Label>
                                <Input
                                    id="role-search"
                                    value={search.data.search}
                                    onChange={(event) =>
                                        search.setData(
                                            'search',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-2"
                                    placeholder={t('Search by name')}
                                />
                                <InputError message={search.errors.search} />
                            </div>
                            <div>
                                <Label htmlFor="role-sort">
                                    {t('Sort by')}
                                </Label>
                                <select
                                    id="role-sort"
                                    className="mt-2 block h-9 rounded-md border bg-background px-3 text-sm"
                                    value={search.data.sort}
                                    onChange={(event) =>
                                        search.setData(
                                            'sort',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="name">{t('Name')}</option>
                                    <option value="created_at">
                                        {t('Created date')}
                                    </option>
                                </select>
                                <InputError message={search.errors.sort} />
                            </div>
                            <div>
                                <Label htmlFor="role-direction">
                                    {t('Order')}
                                </Label>
                                <select
                                    id="role-direction"
                                    className="mt-2 block h-9 rounded-md border bg-background px-3 text-sm"
                                    value={search.data.direction}
                                    onChange={(event) =>
                                        search.setData(
                                            'direction',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="asc">
                                        {t('Ascending')}
                                    </option>
                                    <option value="desc">
                                        {t('Descending')}
                                    </option>
                                </select>
                                <InputError message={search.errors.direction} />
                            </div>
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={search.processing}
                            >
                                {search.processing
                                    ? t('Filtering...')
                                    : t('Apply')}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={search.processing}
                                onClick={() => router.get(index.url())}
                            >
                                {t('Reset')}
                            </Button>
                        </form>
                        <InputError message={errors.role} />
                        {roles.data.length === 0 ? (
                            <EmptyState
                                icon={ShieldCheck}
                                title={t('No roles found')}
                                description={t(
                                    'Try a different search or create a role to define a new set of permissions.',
                                )}
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <caption className="sr-only">
                                        {t(
                                            'Roles, assigned users, permissions and available actions',
                                        )}
                                    </caption>
                                    <thead className="border-b text-muted-foreground">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t('Role')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t('Users')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t('Permissions')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 text-right font-medium"
                                            >
                                                {t('Actions')}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {roles.data.map((role) => (
                                            <tr
                                                key={role.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="p-3 font-medium">
                                                    <div className="flex items-center gap-2">
                                                        {role.name}
                                                        {role.protected && (
                                                            <Badge variant="secondary">
                                                                {t('Protected')}
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    {role.users_count}
                                                </td>
                                                <td className="p-3">
                                                    <details>
                                                        <summary className="cursor-pointer rounded focus-visible:outline-2">
                                                            {
                                                                role.permissions
                                                                    .length
                                                            }{' '}
                                                            {t('permissions')}
                                                        </summary>
                                                        <ul className="mt-2 space-y-1 text-xs text-muted-foreground">
                                                            {role.permissions.map(
                                                                (
                                                                    permission,
                                                                ) => (
                                                                    <li
                                                                        key={
                                                                            permission
                                                                        }
                                                                    >
                                                                        {
                                                                            permission
                                                                        }
                                                                    </li>
                                                                ),
                                                            )}
                                                        </ul>
                                                    </details>
                                                </td>
                                                <td className="p-3">
                                                    <div className="flex justify-end gap-2">
                                                        {role.can.update && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={edit(
                                                                        role.id,
                                                                    )}
                                                                    aria-label={t(
                                                                        'Edit {name}',
                                                                        {
                                                                            name: role.name,
                                                                        },
                                                                    )}
                                                                >
                                                                    {t('Edit')}
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {role.can.delete && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => {
                                                                    deletion.clearErrors();
                                                                    setSelected(
                                                                        role,
                                                                    );
                                                                }}
                                                                aria-label={t(
                                                                    'Delete {name}',
                                                                    {
                                                                        name: role.name,
                                                                    },
                                                                )}
                                                            >
                                                                {t('Delete')}
                                                            </Button>
                                                        )}
                                                        {role.protected && (
                                                            <span className="text-xs text-muted-foreground">
                                                                {t(
                                                                    'System managed',
                                                                )}
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <nav
                            aria-label={t('Role pagination')}
                            className="flex flex-wrap items-center justify-between gap-3 border-t pt-4"
                        >
                            <p className="text-sm text-muted-foreground">
                                {t('Page')} {roles.current_page} {t('of')}{' '}
                                {roles.last_page} · {roles.total} {t('roles')}
                            </p>
                            <div className="flex gap-2">
                                {roles.prev_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={roles.prev_page_url}
                                            preserveScroll
                                        >
                                            {t('Previous')}
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled
                                    >
                                        {t('Previous')}
                                    </Button>
                                )}
                                {roles.next_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={roles.next_page_url}
                                            preserveScroll
                                        >
                                            {t('Next')}
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled
                                    >
                                        {t('Next')}
                                    </Button>
                                )}
                            </div>
                        </nav>
                    </CardContent>
                </Card>
                <Dialog
                    open={selected !== null}
                    onOpenChange={(open) => {
                        if (!open && !deletion.processing) setSelected(null);
                    }}
                >
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>
                                {t('Delete')} {selected?.name}?
                            </DialogTitle>
                            <DialogDescription>
                                {t(
                                    'This permanently removes the role. Roles with assigned users cannot be deleted.',
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <InputError
                            message={
                                (deletion.errors as Record<string, string>).role
                            }
                        />
                        <DialogFooter>
                            <Button
                                variant="outline"
                                disabled={deletion.processing}
                                onClick={() => setSelected(null)}
                            >
                                {t('Cancel')}
                            </Button>
                            <Button
                                variant="destructive"
                                disabled={deletion.processing}
                                onClick={() => {
                                    if (selected)
                                        deletion.delete(
                                            destroy.url(selected.id),
                                            {
                                                preserveScroll: true,
                                                onSuccess: () =>
                                                    setSelected(null),
                                            },
                                        );
                                }}
                            >
                                {deletion.processing
                                    ? t('Deleting...')
                                    : t('Delete role')}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </PageContainer>
        </>
    );
}
RolesIndex.layout = {
    breadcrumbs: [{ title: 'Roles & Permissions', href: index() }],
};
