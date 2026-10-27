import { useTranslation } from '@/hooks/use-language';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ExternalLink, Package } from 'lucide-react';
import { useState } from 'react';
import { ItemDeleteDialog } from '@/components/admin/item-delete-dialog';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { edit, index } from '@/routes/admin/items';
import type { Item } from '@/types/item';

export default function ItemShow({ item }: { item: Item }) {
    const t = useTranslation();
    const [deleting, setDeleting] = useState(false);
    return (
        <>
            <Head title={item.name} />
            <PageContainer>
                <PageHeading
                    title={item.name}
                    description={t('Item details')}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            <Button variant="outline" asChild>
                                <Link href={index()}>
                                    <ArrowLeft aria-hidden="true" />
                                    {t('Items')}
                                </Link>
                            </Button>
                            {item.can.update && (
                                <Button asChild>
                                    <Link href={edit(item.id)}>
                                        {t('Edit item')}
                                    </Link>
                                </Button>
                            )}
                            {item.can.delete && (
                                <Button
                                    variant="outline"
                                    onClick={() => setDeleting(true)}
                                >
                                    {t('Delete')}
                                </Button>
                            )}
                        </div>
                    }
                />
                <Card className="min-w-0 shadow-none">
                    <CardContent className="grid gap-8 md:grid-cols-2">
                        {item.image_url ? (
                            <img
                                src={item.image_url}
                                alt={item.name}
                                className="h-80 w-full rounded-xl bg-muted/40 object-contain"
                            />
                        ) : (
                            <div className="flex h-64 flex-col items-center justify-center gap-3 rounded-xl border border-dashed bg-muted/20 text-muted-foreground">
                                <Package
                                    className="size-10"
                                    aria-hidden="true"
                                />
                                <p className="text-sm">
                                    {t('No image uploaded')}
                                </p>
                            </div>
                        )}
                        <div className="space-y-6">
                            <Badge variant="secondary">
                                {item.category.name}
                            </Badge>
                            <dl className="space-y-5">
                                <div>
                                    <dt className="text-sm text-muted-foreground">
                                        {t('Name')}
                                    </dt>
                                    <dd className="mt-1 font-medium break-words">
                                        {item.name}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-muted-foreground">
                                        {t('Slug')}
                                    </dt>
                                    <dd className="mt-1 font-mono text-sm break-all">
                                        {item.slug}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm text-muted-foreground">
                                        {t('Link')}
                                    </dt>
                                    <dd className="mt-2">
                                        {item.link ? (
                                            <a
                                                href={item.link}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-2 text-sm break-all text-blue-600 underline underline-offset-4 dark:text-blue-400"
                                            >
                                                {item.link}
                                                <ExternalLink
                                                    className="size-4 shrink-0"
                                                    aria-hidden="true"
                                                />
                                                <span className="sr-only">
                                                    {' '}
                                                    {t('(opens in a new tab)')}
                                                </span>
                                            </a>
                                        ) : (
                                            <span className="text-sm text-muted-foreground">
                                                {t('No link provided')}
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </CardContent>
                </Card>
                <ItemDeleteDialog
                    item={deleting ? item : null}
                    onClose={() => setDeleting(false)}
                />
            </PageContainer>
        </>
    );
}
ItemShow.layout = { breadcrumbs: [{ title: 'Items', href: index() }] };
