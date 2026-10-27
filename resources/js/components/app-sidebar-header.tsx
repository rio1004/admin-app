import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { Badge } from '@/components/ui/badge';
import AppearanceTabs from '@/components/appearance-tabs';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';

export function AppSidebarHeader({
    breadcrumbs = [],
}: {
    breadcrumbs?: BreadcrumbItemType[];
}) {
    return (
        <header className="flex min-h-20 shrink-0 flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/50 bg-background px-4 py-3 sm:px-8">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-3">
                <Badge variant="outline" className="hidden lg:inline-flex">
                    Layout preview
                </Badge>
                <AppearanceTabs className="[&_button]:px-2 [&_button_span]:sr-only sm:[&_button_span]:not-sr-only" />
            </div>
        </header>
    );
}
