import { Head, Link, router, useForm } from '@inertiajs/react';
import { Plus, Users } from 'lucide-react';
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
import { create, destroy, edit, index } from '@/routes/admin/users';
import type { AdminUser } from '@/types/admin-user';

type Props = {
    users: {
        data: AdminUser[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    roles: { id: number; name: string }[];
    filters: {
        search?: string;
        role_id?: number | string;
        sort?: string;
        direction?: string;
    };
    canCreate: boolean;
};

export default function UsersIndex({
    users,
    roles,
    filters,
    canCreate,
}: Props) {
    const search = useForm({
        search: filters.search ?? '',
        role_id: filters.role_id ? String(filters.role_id) : '',
        sort:
            typeof filters.sort === 'string' &&
            ['name', 'username', 'email', 'created_at'].includes(filters.sort)
                ? filters.sort
                : 'name',
        direction: filters.direction ?? 'asc',
    });
    const deletion = useForm({});
    const [selected, setSelected] = useState<AdminUser | null>(null);
    const filtered = !!filters.search || !!filters.role_id;
    return (
        <>
            <Head title="Users" />
            <PageContainer>
                <PageHeading
                    title="Users"
                    description="The people behind your workspace. Manage accounts and keep access intentional."
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    Create user
                                </Link>
                            </Button>
                        )
                    }
                />
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
                                <Label htmlFor="user-search">
                                    Search users
                                </Label>
                                <Input
                                    id="user-search"
                                    value={search.data.search}
                                    onChange={(event) =>
                                        search.setData(
                                            'search',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-2"
                                    placeholder="Search name, username, or email"
                                />
                                <InputError message={search.errors.search} />
                            </div>
                            <div className="max-w-full min-w-36">
                                <Label htmlFor="user-role-filter">Role</Label>
                                <select
                                    id="user-role-filter"
                                    className="mt-2 block h-9 w-full rounded-md border bg-background px-3 text-sm"
                                    value={search.data.role_id}
                                    onChange={(event) =>
                                        search.setData(
                                            'role_id',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">All roles</option>
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.id}>
                                            {role.name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={search.errors.role_id} />
                            </div>
                            <div>
                                <Label htmlFor="user-sort">Sort by</Label>
                                <select
                                    id="user-sort"
                                    className="mt-2 block h-9 rounded-md border bg-background px-3 text-sm"
                                    value={search.data.sort}
                                    onChange={(event) =>
                                        search.setData(
                                            'sort',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="name">Name</option>
                                    <option value="username">Username</option>
                                    <option value="email">Email</option>
                                    <option value="created_at">
                                        Created date
                                    </option>
                                </select>
                                <InputError message={search.errors.sort} />
                            </div>
                            <div>
                                <Label htmlFor="user-direction">Order</Label>
                                <select
                                    id="user-direction"
                                    className="mt-2 block h-9 rounded-md border bg-background px-3 text-sm"
                                    value={search.data.direction}
                                    onChange={(event) =>
                                        search.setData(
                                            'direction',
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="asc">Ascending</option>
                                    <option value="desc">Descending</option>
                                </select>
                                <InputError message={search.errors.direction} />
                            </div>
                            <Button
                                type="submit"
                                variant="outline"
                                disabled={search.processing}
                            >
                                {search.processing ? 'Searching...' : 'Apply'}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={search.processing}
                                onClick={() => router.get(index.url())}
                            >
                                Reset
                            </Button>
                        </form>
                        {users.data.length === 0 ? (
                            <EmptyState
                                icon={Users}
                                title={
                                    filtered
                                        ? 'No matching users'
                                        : 'Your team starts here'
                                }
                                description={
                                    filtered
                                        ? 'Try another name, username, email, or role, or reset your filters.'
                                        : 'Create an account to add someone to your workspace.'
                                }
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <caption className="sr-only">
                                        Users, role assignments and available
                                        actions
                                    </caption>
                                    <thead className="border-b text-muted-foreground">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                Name
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                Username
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                Email
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                Roles
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 text-right font-medium"
                                            >
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.data.map((user) => (
                                            <tr
                                                key={user.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="p-3 font-medium">
                                                    <div className="flex items-center gap-2">
                                                        <span className="min-w-28 break-words">
                                                            {user.name}
                                                        </span>
                                                        {user.is_self && (
                                                            <Badge variant="outline">
                                                                You
                                                            </Badge>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="p-3 break-all text-muted-foreground">
                                                    {user.username}
                                                </td>
                                                <td className="p-3 break-all text-muted-foreground">
                                                    {user.email}
                                                </td>
                                                <td className="p-3">
                                                    <div className="flex min-w-24 flex-wrap gap-1">
                                                        {user.roles.length ? (
                                                            user.roles.map(
                                                                (role) => (
                                                                    <Badge
                                                                        key={
                                                                            role.id
                                                                        }
                                                                        variant="secondary"
                                                                    >
                                                                        {
                                                                            role.name
                                                                        }
                                                                    </Badge>
                                                                ),
                                                            )
                                                        ) : (
                                                            <span className="text-xs text-muted-foreground">
                                                                No roles
                                                            </span>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="p-3">
                                                    <div className="flex justify-end gap-2">
                                                        {user.can.update && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={edit(
                                                                        user.id,
                                                                    )}
                                                                    aria-label={`Edit ${user.name}`}
                                                                >
                                                                    Edit
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {user.can.delete && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => {
                                                                    deletion.clearErrors();
                                                                    setSelected(
                                                                        user,
                                                                    );
                                                                }}
                                                                aria-label={`Delete ${user.name}`}
                                                            >
                                                                Delete
                                                            </Button>
                                                        )}
                                                        {!user.can.update &&
                                                            !user.can
                                                                .delete && (
                                                                <span className="text-xs whitespace-nowrap text-muted-foreground">
                                                                    View only
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
                            aria-label="User pagination"
                            className="flex flex-wrap items-center justify-between gap-3 border-t pt-4"
                        >
                            <p className="text-sm text-muted-foreground">
                                Page {users.current_page} of {users.last_page} ·{' '}
                                {users.total} users
                            </p>
                            <div className="flex gap-2">
                                {users.prev_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={users.prev_page_url}
                                            preserveScroll
                                        >
                                            Previous
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled
                                    >
                                        Previous
                                    </Button>
                                )}
                                {users.next_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={users.next_page_url}
                                            preserveScroll
                                        >
                                            Next
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled
                                    >
                                        Next
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
                            <DialogTitle>Delete {selected?.name}?</DialogTitle>
                            <DialogDescription>
                                This permanently deletes the account and its
                                role assignments. You cannot delete your own
                                account from user management, or remove the
                                final administrator.
                            </DialogDescription>
                        </DialogHeader>
                        <InputError
                            message={
                                (deletion.errors as Record<string, string>)
                                    .user ??
                                (deletion.errors as Record<string, string>)
                                    .roles
                            }
                        />
                        <DialogFooter>
                            <Button
                                variant="outline"
                                disabled={deletion.processing}
                                onClick={() => setSelected(null)}
                            >
                                Cancel
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
                                    ? 'Deleting...'
                                    : 'Delete user'}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </PageContainer>
        </>
    );
}
UsersIndex.layout = { breadcrumbs: [{ title: 'Users', href: index() }] };
