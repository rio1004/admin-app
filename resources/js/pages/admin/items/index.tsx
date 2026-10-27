import { useTranslation } from "@/hooks/use-language";
import { Head, Link, router, useForm } from "@inertiajs/react";
import { Package, Plus } from "lucide-react";
import { useState } from "react";
import { EmptyState } from "@/components/admin/empty-state";
import { ItemDeleteDialog } from "@/components/admin/item-delete-dialog";
import { PageContainer } from "@/components/admin/page-container";
import { PageHeading } from "@/components/admin/page-heading";
import InputError from "@/components/input-error";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { create, edit, index, show } from "@/routes/admin/items";
import type { CategoryOption, Item } from "@/types/item";

type Props = {
    items: {
        data: Item[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    categories: CategoryOption[];
    filters: {
        search?: string;
        category_id?: number | string;
        sort?: string;
        direction?: string;
    };
    canCreate: boolean;
};

export default function ItemsIndex({
    items,
    categories,
    filters,
    canCreate,
}: Props) {
    const t = useTranslation();
    const search = useForm({
        search: filters.search ?? "",
        category_id: filters.category_id ? String(filters.category_id) : "",
        sort: filters.sort ?? "created_at",
        direction: filters.direction ?? "desc",
    });
    const [selected, setSelected] = useState<Item | null>(null);
    const filtered = !!filters.search || !!filters.category_id;
    return (
        <>
            <Head title={t("Items")} />
            <PageContainer>
                <PageHeading
                    title={t("Items")}
                    description={t(
                        "Your collection, thoughtfully organized. Find and manage your content in one place.",
                    )}
                    actions={
                        canCreate && (
                            <Button asChild>
                                <Link href={create()}>
                                    <Plus aria-hidden="true" />
                                    {t("Create item")}
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
                                <Label htmlFor="item-search">
                                    {t("Search items")}
                                </Label>
                                <Input
                                    id="item-search"
                                    value={search.data.search}
                                    onChange={(event) =>
                                        search.setData(
                                            "search",
                                            event.target.value,
                                        )
                                    }
                                    className="mt-2"
                                    placeholder={t("Search name or slug")}
                                />
                                <InputError message={search.errors.search} />
                            </div>
                            <div className="max-w-full min-w-36">
                                <Label htmlFor="item-category-filter">
                                    {t("Category")}
                                </Label>
                                <select
                                    id="item-category-filter"
                                    className="mt-2 block h-9 w-full rounded-md border bg-background px-3 text-sm"
                                    value={search.data.category_id}
                                    onChange={(event) =>
                                        search.setData(
                                            "category_id",
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="">
                                        {t("All categories")}
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
                                    message={search.errors.category_id}
                                />
                            </div>
                            <div>
                                <Label htmlFor="item-sort">
                                    {t("Sort by")}
                                </Label>
                                <select
                                    id="item-sort"
                                    className="mt-2 block h-9 rounded-md border bg-background px-3 text-sm"
                                    value={search.data.sort}
                                    onChange={(event) =>
                                        search.setData(
                                            "sort",
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="created_at">
                                        {t("Created date")}
                                    </option>
                                    <option value="name">{t("Name")}</option>
                                    <option value="slug">{t("Slug")}</option>
                                </select>
                                <InputError message={search.errors.sort} />
                            </div>
                            <div>
                                <Label htmlFor="item-direction">
                                    {t("Order")}
                                </Label>
                                <select
                                    id="item-direction"
                                    className="mt-2 block h-9 rounded-md border bg-background px-3 text-sm"
                                    value={search.data.direction}
                                    onChange={(event) =>
                                        search.setData(
                                            "direction",
                                            event.target.value,
                                        )
                                    }
                                >
                                    <option value="desc">
                                        {t("Descending")}
                                    </option>
                                    <option value="asc">
                                        {t("Ascending")}
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
                                    ? t("Searching...")
                                    : t("Apply")}
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                disabled={search.processing}
                                onClick={() => router.get(index.url())}
                            >
                                {t("Reset")}
                            </Button>
                        </form>
                        {items.data.length === 0 ? (
                            <EmptyState
                                icon={Package}
                                title={
                                    filtered
                                        ? t("No matching items")
                                        : t("Your collection starts here")
                                }
                                description={
                                    filtered
                                        ? t(
                                              "Try another search or category, or reset your filters.",
                                          )
                                        : t(
                                              "Add your first item with a category, an optional image, and a link.",
                                          )
                                }
                                actions={
                                    canCreate &&
                                    !filtered && (
                                        <Button asChild>
                                            <Link href={create()}>
                                                <Plus aria-hidden="true" />
                                                {t("Create item")}
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
                                            "Items, categories and available actions",
                                        )}
                                    </caption>
                                    <thead className="border-b text-muted-foreground">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t("Item")}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t("Value")}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t("Description")}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t("Category")}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 font-medium"
                                            >
                                                {t("Link")}
                                            </th>
                                            <th
                                                scope="col"
                                                className="p-3 text-right font-medium"
                                            >
                                                {t("Actions")}
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {items.data.map((item) => (
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
                                                            href={show(item.id)}
                                                            className="min-w-32 font-medium break-words hover:underline"
                                                        >
                                                            {item.name}
                                                        </Link>
                                                    </div>
                                                </td>
                                                <td className="max-w-xs p-3 text-muted-foreground">
                                                    <p className="line-clamp-2 min-w-24 break-words">
                                                        {item.value ??
                                                            t(
                                                                "No value provided",
                                                            )}
                                                    </p>
                                                </td>
                                                <td className="max-w-xs p-3 text-muted-foreground">
                                                    <p className="line-clamp-2 min-w-40 break-words whitespace-pre-wrap">
                                                        {item.description ||
                                                            t(
                                                                "No description provided",
                                                            )}
                                                    </p>
                                                </td>
                                                <td className="p-3">
                                                    <Badge variant="secondary">
                                                        {item.category.name}
                                                    </Badge>
                                                </td>
                                                <td className="p-3 text-sm text-muted-foreground">
                                                    {item.link ? (
                                                        <a
                                                            href={item.link}
                                                            target="_blank"
                                                            rel="noopener noreferrer"
                                                            title={item.link}
                                                            className="block max-w-48 truncate rounded-sm text-primary underline-offset-4 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                        >
                                                            {item.link}
                                                            <span className="sr-only">
                                                                {" "}
                                                                {t(
                                                                    "(opens in a new tab)",
                                                                )}
                                                            </span>
                                                        </a>
                                                    ) : (
                                                        t("No link provided")
                                                    )}
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
                                                                aria-label={t(
                                                                    "View {name}",
                                                                    {
                                                                        name: item.name,
                                                                    },
                                                                )}
                                                            >
                                                                {t("View")}
                                                            </Link>
                                                        </Button>
                                                        {item.can.update && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link
                                                                    href={edit(
                                                                        item.id,
                                                                    )}
                                                                    aria-label={t(
                                                                        "Edit {name}",
                                                                        {
                                                                            name: item.name,
                                                                        },
                                                                    )}
                                                                >
                                                                    {t("Edit")}
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {item.can.delete && (
                                                            <Button
                                                                variant="destructive"
                                                                className="cursor-pointer"
                                                                size="sm"
                                                                onClick={() =>
                                                                    setSelected(
                                                                        item,
                                                                    )
                                                                }
                                                                aria-label={t(
                                                                    "Delete {name}",
                                                                    {
                                                                        name: item.name,
                                                                    },
                                                                )}
                                                            >
                                                                {t("Delete")}
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
                        <nav
                            aria-label={t("Item pagination")}
                            className="flex flex-wrap items-center justify-between gap-3 border-t pt-4"
                        >
                            <p className="text-sm text-muted-foreground">
                                {t("Page")} {items.current_page} {t("of")}{" "}
                                {items.last_page} · {items.total} {t("items")}
                            </p>
                            <div className="flex gap-2">
                                {items.prev_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={items.prev_page_url}
                                            preserveScroll
                                        >
                                            {t("Previous")}
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled
                                    >
                                        {t("Previous")}
                                    </Button>
                                )}
                                {items.next_page_url ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <Link
                                            href={items.next_page_url}
                                            preserveScroll
                                        >
                                            {t("Next")}
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled
                                    >
                                        {t("Next")}
                                    </Button>
                                )}
                            </div>
                        </nav>
                    </CardContent>
                </Card>
                <ItemDeleteDialog
                    item={selected}
                    onClose={() => setSelected(null)}
                />
            </PageContainer>
        </>
    );
}
ItemsIndex.layout = { breadcrumbs: [{ title: "Items", href: index() }] };
