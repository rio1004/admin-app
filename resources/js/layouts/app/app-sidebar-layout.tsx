import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { useNavigationPending } from '@/hooks/use-navigation-pending';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    const pending = useNavigationPending();
    return (
        <AppShell variant="sidebar">
            <AppSidebar />
            <AppContent
                variant="sidebar"
                aria-busy={pending}
                className="min-w-0 overflow-x-clip bg-slate-50/80 dark:bg-background"
            >
                <AppSidebarHeader breadcrumbs={breadcrumbs} pending={pending} />
                {children}
            </AppContent>
        </AppShell>
    );
}
