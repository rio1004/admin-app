import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Folders,
    Package,
    Plus,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { EmptyState } from '@/components/admin/empty-state';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import { SummaryCard } from '@/components/admin/summary-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { index as usersIndex } from '@/routes/admin/users';
import { index as rolesIndex } from '@/routes/admin/roles';
import { index as categoriesIndex } from '@/routes/admin/categories';
import { create, edit, index as itemsIndex, show } from '@/routes/admin/items';
import type { Item } from '@/types/item';

type Props = {
    counts: Partial<Record<'users' | 'roles' | 'items' | 'categories', number>>;
    recentItems?: Item[];
    canCreateItem: boolean;
};

export default function Dashboard({
    counts,
    recentItems,
    canCreateItem,
}: Props) {
    const { auth } = usePage().props;
    return (
        <>
            <Head title="Dashboard" />
            <PageContainer>
                <PageHeading
                    title="Dashboard"
                    description="Your workspace, at a glance."
                    actions={
                        canCreateItem && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    Create item
                                </Link>
                            </Button>
                        )
                    }
                />
                <section className="relative overflow-hidden rounded-2xl border border-cyan-100 bg-gradient-to-r from-cyan-50 to-blue-50 p-6 sm:p-8 dark:border-cyan-900 dark:from-cyan-950/40 dark:to-blue-950/40">
                    <div
                        className="pointer-events-none absolute -top-16 -right-12 size-56 rounded-full border-[32px] border-blue-200/30 dark:border-blue-800/20"
                        aria-hidden="true"
                    />
                    <div className="relative">
                        <Badge
                            variant="outline"
                            className="mb-4 border-cyan-200 bg-background/60"
                        >
                            Your admin workspace
                        </Badge>
                        <h2 className="text-xl font-semibold tracking-tight break-words sm:text-2xl">
                            Welcome back, {auth.user.name}.
                        </h2>
                        <p className="mt-3 max-w-xl text-sm leading-6 text-muted-foreground">
                            A quick look at your team and collection. Choose a
                            section to get started.
                        </p>
                    </div>
                </section>
                {Object.keys(counts).length > 0 ? (
                    <section
                        aria-label="Workspace totals"
                        className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                    >
                        {counts.users !== undefined && (
                            <Link
                                href={usersIndex()}
                                className="rounded-xl focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none"
                            >
                                <SummaryCard
                                    title="Users"
                                    value={counts.users}
                                    icon={Users}
                                    tone="blue"
                                    caption="Registered accounts"
                                />
                            </Link>
                        )}
                        {counts.roles !== undefined && (
                            <Link
                                href={rolesIndex()}
                                className="rounded-xl focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none"
                            >
                                <SummaryCard
                                    title="Roles"
                                    value={counts.roles}
                                    icon={ShieldCheck}
                                    tone="violet"
                                    caption="Defined roles"
                                />
                            </Link>
                        )}
                        {counts.items !== undefined && (
                            <Link
                                href={itemsIndex()}
                                className="rounded-xl focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none"
                            >
                                <SummaryCard
                                    title="Items"
                                    value={counts.items}
                                    icon={Package}
                                    tone="cyan"
                                    caption="Items in your collection"
                                />
                            </Link>
                        )}
                        {counts.categories !== undefined && (
                            <Link
                                href={categoriesIndex()}
                                className="rounded-xl focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:outline-none"
                            >
                                <SummaryCard
                                    title="Categories"
                                    value={counts.categories}
                                    icon={Folders}
                                    tone="amber"
                                    caption="Content categories"
                                />
                            </Link>
                        )}
                    </section>
                ) : (
                    <EmptyState
                        icon={ShieldCheck}
                        title="Your workspace is ready"
                        description="Ask an administrator to enable the sections you need. Your available sections appear in the sidebar."
                    />
                )}
                {recentItems !== undefined && (
                    <Card className="min-w-0 shadow-none">
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <CardTitle>
                                        <h2>Recent items</h2>
                                    </CardTitle>
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        The latest additions to your collection.
                                    </p>
                                </div>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={itemsIndex()}>
                                        View all{' '}
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            {recentItems.length === 0 ? (
                                <EmptyState
                                    icon={Package}
                                    title="Your collection starts here"
                                    description={
                                        canCreateItem
                                            ? 'Add your first item to start building your collection.'
                                            : 'Items will appear here when your team adds them.'
                                    }
                                    actions={
                                        canCreateItem && (
                                            <Button asChild>
                                                <Link href={create()}>
                                                    <Plus aria-hidden="true" />
                                                    Create item
                                                </Link>
                                            </Button>
                                        )
                                    }
                                />
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-sm">
                                        <caption className="sr-only">
                                            The five most recently created items
                                        </caption>
                                        <thead className="border-b text-muted-foreground">
                                            <tr>
                                                <th
                                                    scope="col"
                                                    className="p-3 font-medium"
                                                >
                                                    Item
                                                </th>
                                                <th
                                                    scope="col"
                                                    className="p-3 font-medium"
                                                >
                                                    Category
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
                                            {recentItems.map((item) => (
                                                <tr
                                                    key={item.id}
                                                    className="border-b last:border-0"
                                                >
                                                    <td className="p-3">
                                                        <div className="flex items-center gap-3">
                                                            {item.image_url ? (
                                                                <img
                                                                    src={
                                                                        item.image_url
                                                                    }
                                                                    alt=""
                                                                    loading="lazy"
                                                                    className="size-12 shrink-0 rounded-xl bg-muted object-cover"
                                                                />
                                                            ) : (
                                                                <span className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300">
                                                                    <Package
                                                                        className="size-5"
                                                                        aria-hidden="true"
                                                                    />
                                                                </span>
                                                            )}
                                                            <Link
                                                                href={show(
                                                                    item.id,
                                                                )}
                                                                className="min-w-32 font-medium break-words hover:underline"
                                                            >
                                                                {item.name}
                                                            </Link>
                                                        </div>
                                                    </td>
                                                    <td className="p-3">
                                                        <Badge variant="secondary">
                                                            {item.category.name}
                                                        </Badge>
                                                    </td>
                                                    <td className="p-3">
                                                        <div className="flex justify-end gap-2">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={show(
                                                                        item.id,
                                                                    )}
                                                                    aria-label={`View ${item.name}`}
                                                                >
                                                                    View
                                                                </Link>
                                                            </Button>
                                                            {item.can
                                                                .update && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    asChild
                                                                >
                                                                    <Link
                                                                        href={edit(
                                                                            item.id,
                                                                        )}
                                                                        aria-label={`Edit ${item.name}`}
                                                                    >
                                                                        Edit
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}
            </PageContainer>
        </>
    );
}
Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] };
