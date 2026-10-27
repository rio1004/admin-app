import { useTranslation } from '@/hooks/use-language';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Folders, Plus } from 'lucide-react';
import { useState } from 'react';
import { EmptyState } from '@/components/admin/empty-state';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import InputError from '@/components/input-error';
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
import { create, destroy, edit, index } from '@/routes/admin/categories';

type Category = {
    id: number;
    name: string;
    slug: string;
    can: { update: boolean; delete: boolean };
};
type Props = {
    categories: {
        data: Category[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search?: string; sort?: string; direction?: string };
    canCreate: boolean;
};

export default function CategoriesIndex({
    categories,
    filters,
    canCreate,
}: Props) {
    const t = useTranslation();
    const search = useForm({
        search: filters.search ?? '',
        sort: filters.sort ?? 'name',
        direction: filters.direction ?? 'asc',
    });
    const deletion = useForm({});
    const [selected, setSelected] = useState<Category | null>(null);
    const { errors } = usePage().props;
    return (
        <>
            <Head title={t('Categories')} />
            <PageContainer>
                <PageHeading
                    title={t('Categories')}
                    description={t(
                        'A place for everything. Organize your content into simple collections.',
                    )}
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    {t('Create category')}
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
                                <Label htmlFor="category-search">
                                    {t('Search categories')}
                                </Label>
                                <Input
                                    id="category-search"
                                    value={search.data.search}
                                    onChange={(event) =>
                                        search.setData(
                                            'search',
                                            event.target.value,
                                        )
                                    }
                                    className="mt-2"
                                    placeholder={t('Search name or slug')}
                                />
                                <InputError message={search.errors.search} />
                            </div>
                            <div>
                                <Label htmlFor="category-sort">
                                    {t('Sort by')}
                                </Label>
                                <select
                                    id="category-sort"
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
                                    <option value="slug">{t('Slug')}</option>
                                    <option value="created_at">
                                        {t('Created date')}
                                    </option>
                                </select>
                                <InputError message={search.errors.sort} />
                            </div>
                            <div>
                                <Label htmlFor="category-direction">
                                    {t('Order')}
                                </Label>
                                <select
                                    id="category-direction"
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
                                    ? t('Searching...')
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
                        <InputError message={errors.category} />
                        {categories.data.length === 0 ? (
                            <EmptyState
                                icon={Folders}
                                title={
                                    filters.search
                                        ? t('No matching categories')
                                        : t('Your categories start here')
                                }
                                description={
                                    filters.search
                                        ? t(
                                              'Try a different name or slug, or reset your search.',
                                          )
                                        : t(
                                              'Create your first category to start organizing your content.',
                                          )
                                }
                                actions={
                                    canCreate &&
                                    !filters.search && (
                                        <Button asChild>
                                            <Link href={create()}>
                                                <Plus aria-hidden="true" />
                                                {t('Create category')}
                                            </Link>
                                        </Button>
                                    )
                                }
                            />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <caption className="sr-only">
                                        {t(
                                            'Categories, unique slugs and available actions',
                                        )}
                                    </caption>
                                    <thead className="border-b text-muted-foreground">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t('Category')}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t('Slug')}
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
                                        {categories.data.map((category) => (
                                            <tr
                                                key={category.id}
                                                className="border-b last:border-0"
                                            >
                                                <td className="p-3 font-medium">
                                                    <div className="flex items-center gap-3">
                                                        <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-cyan-50 text-cyan-700 dark:bg-cyan-950/40 dark:text-cyan-300">
                                                            <Folders
                                                                className="size-4"
                                                                aria-hidden="true"
                                                            />
                                                        </span>
                                                        <span className="min-w-32 break-words">
                                                            {category.name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="p-3 font-mono text-xs text-muted-foreground">
                                                    {category.slug}
                                                </td>
                                                <td className="p-3">
                                                    <div className="flex justify-end gap-2">
                                                        {category.can
                                                            .update && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={edit(
                                                                        category.id,
                                                                    )}
                                                                    aria-label={t(
                                                                        'Edit {name}',
                                                                        {
                                                                            name: category.name,
                                                                        },
                                                                    )}
                                                                >
                                                                    {t('Edit')}
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {category.can
                                                            .delete && (
                                                            <Button
                                                                variant="destructive"
                                                                className='cursor-pointer'
                                                                size="sm"
                                                                onClick={() => {
                                                                    deletion.clearErrors();
                                                                    setSelected(
                                                                        category,
                                                                    );
                                                                }}
                                                                aria-label={t(
                                                                    'Delete {name}',
                                                                    {
                                                                        name: category.name,
                                                                    },
                                                                )}
                                                            >
                                                                {t('Delete')}
                                                            </Button>
                                                        )}
                                                        {!category.can.update &&
                                                            !category.can
                                                                .delete && (
                                                                <span className="text-xs whitespace-nowrap text-muted-foreground">
                                                                    {t(
                                                                        'View only',
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
                            aria-label={t('Category pagination')}
                            className="flex flex-wrap items-center justify-between gap-3 border-t pt-4"
                        >
                            <p className="text-sm text-muted-foreground">
                                {t('Page')} {categories.current_page} {t('of')}{' '}
                                {categories.last_page} · {categories.total}{' '}
                                {t('categories')}
                            </p>
                            <div className="flex gap-2">
                                {categories.prev_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={categories.prev_page_url}
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
                                {categories.next_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={categories.next_page_url}
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
                                    'This permanently removes the category and cannot be undone. Categories containing items must be kept until their items are reassigned or deleted.',
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <InputError
                            message={
                                (deletion.errors as Record<string, string>)
                                    .category
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
                                    : t('Delete category')}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </PageContainer>
        </>
    );
}
CategoriesIndex.layout = {
    breadcrumbs: [{ title: 'Categories', href: index() }],
};
