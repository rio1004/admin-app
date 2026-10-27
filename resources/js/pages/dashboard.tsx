import { Head, usePage } from '@inertiajs/react';
import {
    ArrowDown,
    Folders,
    Package,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-react';
import { EmptyState } from '@/components/admin/empty-state';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import { SummaryCard } from '@/components/admin/summary-card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';

const samples = [
    {
        name: 'Studio notebook',
        category: 'Stationery',
        initials: 'SN',
        color: 'bg-violet-100 text-violet-700',
    },
    {
        name: 'Everyday carry',
        category: 'Accessories',
        initials: 'EC',
        color: 'bg-cyan-100 text-cyan-800',
    },
    {
        name: 'Desk essentials',
        category: 'Workspace',
        initials: 'DE',
        color: 'bg-amber-100 text-amber-800',
    },
];

export default function Dashboard() {
    const { auth } = usePage().props;
    return (
        <>
            <Head title="Dashboard preview" />
            <PageContainer>
                <PageHeading
                    title="Dashboard"
                    description="A little clarity for your day. Your workspace, at a glance."
                    actions={
                        <Button variant="outline" asChild>
                            <a href="#recent-items">
                                Explore preview <ArrowDown aria-hidden="true" />
                            </a>
                        </Button>
                    }
                />
                <section
                    aria-label="Preview notice"
                    className="flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-900 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200"
                >
                    <Sparkles
                        className="mt-1 size-4 shrink-0"
                        aria-hidden="true"
                    />
                    <p>
                        <strong>Visual preview only.</strong> All counts and
                        item examples below are placeholders. Management
                        features are coming in a later milestone.
                    </p>
                </section>
                <section className="relative overflow-hidden rounded-2xl border border-cyan-100 bg-gradient-to-r from-cyan-50 to-blue-50 p-6 sm:p-8 dark:border-cyan-900 dark:from-cyan-950/40 dark:to-blue-950/40">
                    <div
                        className="pointer-events-none absolute -top-16 -right-12 size-56 rounded-full border-[32px] border-blue-200/30 dark:border-blue-800/20"
                        aria-hidden="true"
                    />
                    <div className="relative max-w-xl">
                        <Badge
                            variant="outline"
                            className="mb-4 border-cyan-200 bg-background/60"
                        >
                            Your admin workspace
                        </Badge>
                        <h2 className="text-xl font-semibold tracking-tight sm:text-2xl">
                            Welcome back, {auth.user.name}.
                        </h2>
                        <p className="mt-3 text-sm leading-6 text-muted-foreground">
                            A calm space to organize your content and your team.
                            Here’s a first look at how your dashboard will come
                            together.
                        </p>
                    </div>
                </section>
                <section
                    aria-label="Placeholder summary"
                    className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4"
                >
                    <SummaryCard
                        title="Users"
                        value="128"
                        icon={Users}
                        tone="blue"
                        caption="Placeholder · team and account overview"
                    />
                    <SummaryCard
                        title="Roles"
                        value="4"
                        icon={ShieldCheck}
                        tone="violet"
                        caption="Placeholder · access management"
                    />
                    <SummaryCard
                        title="Items"
                        value="256"
                        icon={Package}
                        tone="cyan"
                        caption="Placeholder · your content collection"
                    />
                    <SummaryCard
                        title="Categories"
                        value="12"
                        icon={Folders}
                        tone="amber"
                        caption="Placeholder · organized collections"
                    />
                </section>
                <div className="grid items-start gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(0,1fr)]">
                    <Card
                        id="recent-items"
                        className="min-w-0 scroll-mt-6 shadow-none"
                    >
                        <CardHeader>
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <CardTitle>
                                    <h2>Recent items</h2>
                                </CardTitle>
                                <Badge variant="secondary">
                                    Placeholder content
                                </Badge>
                            </div>
                            <CardDescription>
                                A preview of your latest content. These examples
                                are not saved records.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <caption className="sr-only">
                                        Sample items for visual review only
                                    </caption>
                                    <thead className="border-b text-xs text-muted-foreground">
                                        <tr>
                                            <th
                                                scope="col"
                                                className="pb-3 font-medium"
                                            >
                                                Item
                                            </th>
                                            <th
                                                scope="col"
                                                className="px-4 pb-3 font-medium"
                                            >
                                                Category
                                            </th>
                                            <th
                                                scope="col"
                                                className="pb-3 text-right font-medium"
                                            >
                                                Source
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {samples.map((item) => (
                                            <tr
                                                key={item.name}
                                                className="border-b last:border-0"
                                            >
                                                <td className="py-4">
                                                    <div className="flex items-center gap-3">
                                                        <span
                                                            className={`flex size-11 shrink-0 items-center justify-center rounded-xl text-xs font-semibold ${item.color}`}
                                                            aria-hidden="true"
                                                        >
                                                            {item.initials}
                                                        </span>
                                                        <span className="min-w-32 font-medium">
                                                            {item.name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-4 whitespace-nowrap text-muted-foreground">
                                                    {item.category}
                                                </td>
                                                <td className="py-4 text-right text-xs text-muted-foreground">
                                                    Sample
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="shadow-none">
                        <CardHeader>
                            <CardTitle>
                                <h2>Room for your content</h2>
                            </CardTitle>
                            <CardDescription>
                                Empty-state pattern preview
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <EmptyState
                                icon={Package}
                                title="Your collection starts here"
                                description="When item management is available, this space will guide you through adding your first item."
                            />
                            <p className="mt-4 text-xs leading-5 text-muted-foreground">
                                Layout milestone · creation and editing are not
                                available in this preview.
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </PageContainer>
        </>
    );
}

Dashboard.layout = { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] };
