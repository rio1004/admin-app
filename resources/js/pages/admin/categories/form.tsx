import { useTranslation } from '@/hooks/use-language';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/admin/categories';
import { edit as profileEdit } from '@/routes/profile';

type Props = {
    category: { id: number; name: string; slug: string } | null;
    canViewCategories: boolean;
};

export default function CategoryForm({ category, canViewCategories }: Props) {
    const t = useTranslation();
    const form = useForm({ name: category?.name ?? '' });
    return (
        <>
            <Head
                title={category ? t('Edit category') : t('Create category')}
            />
            <PageContainer>
                <PageHeading
                    title={
                        category
                            ? t('Edit {name}', { name: category.name })
                            : t('Create category')
                    }
                    description={t(
                        'Keep your content organized with clear, reusable categories.',
                    )}
                />
                <Card className="min-w-0 shadow-none">
                    <CardContent>
                        <form
                            aria-busy={form.processing}
                            className="space-y-8"
                            onSubmit={(event) => {
                                event.preventDefault();
                                if (category)
                                    form.put(update.url(category.id), {
                                        preserveScroll: true,
                                    });
                                else
                                    form.post(store.url(), {
                                        onSuccess: () => form.reset(),
                                    });
                            }}
                        >
                            <div className="max-w-xl space-y-2">
                                <Label htmlFor="category-name">
                                    {t('Category name')}
                                </Label>
                                <Input
                                    id="category-name"
                                    name="name"
                                    required
                                    autoFocus
                                    maxLength={120}
                                    value={form.data.name}
                                    disabled={form.processing}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    aria-invalid={!!form.errors.name}
                                    aria-describedby="category-name-help category-name-error"
                                    placeholder="e.g. Workspace essentials"
                                />
                                <p
                                    id="category-name-help"
                                    className="text-xs text-muted-foreground"
                                >
                                    {t(
                                        'Up to 120 characters. Categories may share a name; each gets a unique slug.',
                                    )}
                                </p>
                                <InputError
                                    id="category-name-error"
                                    message={form.errors.name}
                                />
                            </div>
                            <div className="max-w-xl space-y-2">
                                <Label htmlFor="category-slug">
                                    {t('Slug')}
                                </Label>
                                <Input
                                    id="category-slug"
                                    readOnly
                                    value={category?.slug ?? ''}
                                    placeholder={t('Generated when you save')}
                                    aria-describedby="category-slug-help"
                                    className="bg-muted/50 font-mono text-sm"
                                />
                                <p
                                    id="category-slug-help"
                                    className="text-sm leading-6 text-muted-foreground"
                                >
                                    {t(
                                        'Generated automatically on creation. The slug stays the same when you rename this category.',
                                    )}
                                </p>
                            </div>
                            <div className="flex flex-wrap items-center gap-3 border-t pt-5">
                                <Button disabled={form.processing}>
                                    {form.processing
                                        ? t('Saving...')
                                        : category
                                          ? t('Save changes')
                                          : t('Create category')}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={
                                            canViewCategories
                                                ? index()
                                                : profileEdit()
                                        }
                                    >
                                        {t('Cancel')}
                                    </Link>
                                </Button>
                                {form.recentlySuccessful && (
                                    <p
                                        role="status"
                                        className="text-sm text-muted-foreground"
                                    >
                                        {t('Category saved.')}
                                    </p>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </PageContainer>
        </>
    );
}
CategoryForm.layout = { breadcrumbs: [] };
