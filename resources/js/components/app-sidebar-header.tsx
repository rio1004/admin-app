import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Badge } from '@/components/ui/badge';
import AppearanceTabs from '@/components/appearance-tabs';
import { LoaderCircle } from 'lucide-react';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
    pending = false,
}: {
    breadcrumbs?: BreadcrumbItemType[];
    pending?: boolean;
}) {
    return (
        <header className="flex min-h-20 shrink-0 flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/50 bg-background px-4 py-3 sm:px-8">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-3">
                <span
                    role="status"
                    className="flex items-center gap-2 text-xs text-muted-foreground"
                >
                    {pending && (
                        <>
                            <LoaderCircle
                                className="size-4 animate-spin motion-reduce:animate-none"
                                aria-hidden="true"
                            />
                            <span className="sr-only sm:not-sr-only">
                                Loading...
                            </span>
                        </>
                    )}
                </span>
                <Badge variant="outline" className="hidden lg:inline-flex">
                    Admin workspace
                </Badge>
                <AppearanceTabs className="[&_button]:px-2 [&_button_span]:sr-only sm:[&_button_span]:not-sr-only" />
            </div>
        </header>
    );
}
