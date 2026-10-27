import { Head, Link, useForm } from '@inertiajs/react';
import { Package, Upload } from 'lucide-react';
import { useRef, useState } from 'react';
import { EmptyState } from '@/components/admin/empty-state';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create as createCategory } from '@/routes/admin/categories';
import { index, store, update } from '@/routes/admin/items';
import { edit as profileEdit } from '@/routes/profile';
import type { CategoryOption, Item } from '@/types/item';

type Props = {
    item: Item | null;
    categories: CategoryOption[];
    canViewItems: boolean;
    canCreateCategory: boolean;
};

export default function ItemForm({
    item,
    categories,
    canViewItems,
    canCreateCategory,
}: Props) {
    const fileInput = useRef<HTMLInputElement>(null);
    const dragDepth = useRef(0);
    const [isDragging, setIsDragging] = useState(false);
    const form = useForm<{
        name: string;
        category_id: string;
        link: string;
        image: File | null;
        remove_image: boolean;
        _method: string;
    }>({
        name: item?.name ?? '',
        category_id: item ? String(item.category_id) : '',
        link: item?.link ?? '',
        image: null,
        remove_image: false,
        _method: item ? 'put' : 'post',
    });
    const uploadDisabled = form.processing || form.data.remove_image;

    function selectImage(files: FileList | null) {
        if (uploadDisabled || !files?.length) return;

        form.clearErrors('image');
        const image = files[0];
        if (files.length !== 1) {
            form.setError('image', 'Choose one image at a time.');
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(image.type)) {
            form.setError('image', 'Choose a JPEG, PNG, or WebP image.');
            return;
        }
        if (image.size > 5 * 1024 * 1024) {
            form.setError('image', 'The image must be no larger than 5 MB.');
            return;
        }
        form.setData('image', image);
        if (fileInput.current) fileInput.current.value = '';
    }
    return (
        <>
            <Head title={item ? 'Edit item' : 'Create item'} />
            <PageContainer>
                <PageHeading
                    title={item ? `Edit ${item.name}` : 'Create item'}
                    description="Give your content a home, an image, and an optional destination."
                />
                {categories.length === 0 ? (
                    <Card className="min-w-0 shadow-none">
                        <CardContent>
                            <EmptyState
                                icon={Package}
                                title="Create a category first"
                                description="Every item needs a category. Add a category before creating an item."
                                actions={
                                    canCreateCategory ? (
                                        <Button asChild>
                                            <Link href={createCategory()}>
                                                Create category
                                            </Link>
                                        </Button>
                                    ) : (
                                        <p className="text-sm text-muted-foreground">
                                            Ask a category manager to create
                                            one.
                                        </p>
                                    )
                                }
                            />
                        </CardContent>
                    </Card>
                ) : (
                    <Card className="min-w-0 shadow-none">
                        <CardContent>
                            <form
                                aria-busy={form.processing}
                                className="space-y-7"
                                onSubmit={(event) => {
                                    event.preventDefault();
                                    form.post(
                                        item
                                            ? update.url(item.id)
                                            : store.url(),
                                        {
                                            forceFormData: true,
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                form.setData('image', null);
                                                form.setData(
                                                    'remove_image',
                                                    false,
                                                );
                                                if (fileInput.current)
                                                    fileInput.current.value =
                                                        '';
                                                if (!item) form.reset();
                                            },
                                        },
                                    );
                                }}
                            >
                                <div className="grid gap-7 lg:grid-cols-2">
                                    <div className="space-y-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="item-name">
                                                Item name
                                            </Label>
                                            <Input
                                                id="item-name"
                                                required
                                                autoFocus
                                                maxLength={120}
                                                value={form.data.name}
                                                disabled={form.processing}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'name',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={
                                                    !!form.errors.name
                                                }
                                                aria-describedby="item-name-error"
                                            />
                                            <InputError
                                                id="item-name-error"
                                                message={form.errors.name}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="item-category">
                                                Category
                                            </Label>
                                            <select
                                                id="item-category"
                                                required
                                                disabled={form.processing}
                                                value={form.data.category_id}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'category_id',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={
                                                    !!form.errors.category_id
                                                }
                                                aria-describedby="item-category-error"
                                                className="block h-10 w-full rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="" disabled>
                                                    Select a category
                                                </option>
                                                {categories.map((category) => (
                                                    <option
                                                        key={category.id}
                                                        value={category.id}
                                                    >
                                                        {category.name}
                                                    </option>
                                                ))}
                                            </select>
                                            <InputError
                                                id="item-category-error"
                                                message={
                                                    form.errors.category_id
                                                }
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="item-link">
                                                Link{' '}
                                                <span className="font-normal text-muted-foreground">
                                                    (optional)
                                                </span>
                                            </Label>
                                            <Input
                                                id="item-link"
                                                type="url"
                                                maxLength={2048}
                                                placeholder="https://example.com"
                                                value={form.data.link}
                                                disabled={form.processing}
                                                onChange={(event) =>
                                                    form.setData(
                                                        'link',
                                                        event.target.value,
                                                    )
                                                }
                                                aria-invalid={
                                                    !!form.errors.link
                                                }
                                                aria-describedby="item-link-help item-link-error"
                                            />
                                            <p
                                                id="item-link-help"
                                                className="text-xs text-muted-foreground"
                                            >
                                                HTTP and HTTPS links only.
                                            </p>
                                            <InputError
                                                id="item-link-error"
                                                message={form.errors.link}
                                            />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="item-slug">
                                                Slug
                                            </Label>
                                            <Input
                                                id="item-slug"
                                                readOnly
                                                value={item?.slug ?? ''}
                                                placeholder="Generated when you save"
                                                className="bg-muted/50 font-mono"
                                            />
                                            <p className="text-xs leading-5 text-muted-foreground">
                                                Generated on creation and kept
                                                when you rename the item.
                                            </p>
                                        </div>
                                    </div>
                                    <div className="space-y-4 rounded-xl border bg-muted/20 p-5">
                                        <Label htmlFor="item-image">
                                            Image{' '}
                                            <span className="font-normal text-muted-foreground">
                                                (optional)
                                            </span>
                                        </Label>
                                        <button
                                            type="button"
                                            disabled={uploadDisabled}
                                            onClick={() =>
                                                fileInput.current?.click()
                                            }
                                            onDragEnter={(event) => {
                                                event.preventDefault();
                                                if (uploadDisabled) return;
                                                dragDepth.current++;
                                                setIsDragging(true);
                                            }}
                                            onDragOver={(event) => {
                                                event.preventDefault();
                                                event.dataTransfer.dropEffect =
                                                    uploadDisabled
                                                        ? 'none'
                                                        : 'copy';
                                            }}
                                            onDragLeave={(event) => {
                                                event.preventDefault();
                                                dragDepth.current = Math.max(
                                                    0,
                                                    dragDepth.current - 1,
                                                );
                                                if (dragDepth.current === 0)
                                                    setIsDragging(false);
                                            }}
                                            onDrop={(event) => {
                                                event.preventDefault();
                                                dragDepth.current = 0;
                                                setIsDragging(false);
                                                selectImage(
                                                    event.dataTransfer.files,
                                                );
                                            }}
                                            aria-label="Upload item image: drop a file or browse"
                                            aria-describedby="item-image-help item-image-error"
                                            className={`block w-full space-y-3 rounded-lg border-2 border-dashed p-4 text-center transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:cursor-not-allowed disabled:opacity-50 ${isDragging ? 'border-primary bg-primary/10' : 'border-border hover:border-primary/50 hover:bg-muted/50'}`}
                                        >
                                            {item?.image_url &&
                                            !form.data.remove_image ? (
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className="h-48 w-full rounded-lg bg-muted object-contain"
                                                />
                                            ) : (
                                                <div className="flex h-40 items-center justify-center rounded-lg border border-dashed text-muted-foreground">
                                                    <Package
                                                        className="size-8"
                                                        aria-hidden="true"
                                                    />
                                                    <span className="ml-3 text-sm">
                                                        {form.data.remove_image
                                                            ? 'Image will be removed'
                                                            : (form.data.image
                                                                  ?.name ??
                                                              'No image selected')}
                                                    </span>
                                                </div>
                                            )}
                                            <span className="flex items-center justify-center gap-2 text-sm font-medium">
                                                <Upload
                                                    className="size-4"
                                                    aria-hidden="true"
                                                />
                                                {isDragging
                                                    ? 'Drop image here'
                                                    : 'Drag and drop an image, or click to browse'}
                                            </span>
                                        </button>
                                        <Input
                                            ref={fileInput}
                                            id="item-image"
                                            type="file"
                                            accept="image/jpeg,image/png,image/webp"
                                            disabled={
                                                form.processing ||
                                                form.data.remove_image
                                            }
                                            onChange={(event) =>
                                                selectImage(event.target.files)
                                            }
                                            aria-describedby="item-image-help item-image-error"
                                        />
                                        <p
                                            id="item-image-help"
                                            className="text-xs leading-5 text-muted-foreground"
                                        >
                                            JPEG, PNG, or WebP. Up to 5 MB and
                                            4096 × 4096 pixels. Leave empty to
                                            keep the current image.
                                        </p>
                                        <InputError
                                            id="item-image-error"
                                            message={form.errors.image}
                                        />
                                        {form.data.image && (
                                            <div className="flex flex-wrap items-center gap-3 text-sm">
                                                <span role="status">
                                                    {form.data.image.name}
                                                </span>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    disabled={form.processing}
                                                    onClick={() => {
                                                        form.clearErrors(
                                                            'image',
                                                        );
                                                        form.setData(
                                                            'image',
                                                            null,
                                                        );
                                                        if (fileInput.current)
                                                            fileInput.current.value =
                                                                '';
                                                    }}
                                                >
                                                    Clear selection
                                                </Button>
                                            </div>
                                        )}
                                        {item?.image_url && (
                                            <div className="flex items-center gap-3">
                                                <Checkbox
                                                    id="remove-image"
                                                    checked={
                                                        form.data.remove_image
                                                    }
                                                    disabled={form.processing}
                                                    onCheckedChange={(
                                                        checked,
                                                    ) => {
                                                        form.setData(
                                                            'remove_image',
                                                            checked === true,
                                                        );
                                                        if (checked) {
                                                            form.setData(
                                                                'image',
                                                                null,
                                                            );
                                                            if (
                                                                fileInput.current
                                                            )
                                                                fileInput.current.value =
                                                                    '';
                                                        }
                                                    }}
                                                />
                                                <Label htmlFor="remove-image">
                                                    Remove current image
                                                </Label>
                                            </div>
                                        )}
                                        <InputError
                                            message={form.errors.remove_image}
                                        />
                                    </div>
                                </div>
                                {form.progress && (
                                    <div
                                        role="status"
                                        className="space-y-2 text-sm text-muted-foreground"
                                    >
                                        <p>
                                            Uploading:{' '}
                                            {form.progress.percentage ?? 0}%
                                        </p>
                                        <progress
                                            className="w-full"
                                            value={
                                                form.progress.percentage ?? 0
                                            }
                                            max={100}
                                            aria-label="Image upload progress"
                                        />
                                    </div>
                                )}
                                <div className="flex flex-wrap items-center gap-3 border-t pt-5">
                                    <Button disabled={form.processing}>
                                        {form.processing
                                            ? 'Saving...'
                                            : item
                                              ? 'Save changes'
                                              : 'Create item'}
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link
                                            href={
                                                canViewItems
                                                    ? index()
                                                    : profileEdit()
                                            }
                                        >
                                            Cancel
                                        </Link>
                                    </Button>
                                    {form.recentlySuccessful && (
                                        <p
                                            role="status"
                                            className="text-sm text-muted-foreground"
                                        >
                                            Item saved.
                                        </p>
                                    )}
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}
            </PageContainer>
        </>
    );
}
ItemForm.layout = { breadcrumbs: [] };
