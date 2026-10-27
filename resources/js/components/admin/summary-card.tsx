import { useTranslation } from '@/hooks/use-language';
import type { LucideIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const tones = {
    blue: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300',
    violet: 'bg-violet-50 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300',
    cyan: 'bg-cyan-50 text-cyan-800 dark:bg-cyan-950/40 dark:text-cyan-300',
    amber: 'bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300',
};

export function SummaryCard({
    title,
    value,
    icon: Icon,
    tone = 'blue',
    caption,
}: {
    title: string;
    value: number;
    icon: LucideIcon;
    tone?: keyof typeof tones;
    caption: string;
}) {
    const t = useTranslation();
    return (
        <Card className={cn('border-0 shadow-none', tones[tone])}>
            <CardContent>
                <div className="mb-5 flex items-center justify-between gap-3">
                    <span className="flex size-11 items-center justify-center rounded-xl bg-white/70 dark:bg-white/10">
                        <Icon className="size-5" aria-hidden="true" />
                    </span>
                    <span className="text-xs font-medium">{t('Total')}</span>
                </div>
                <p className="text-sm font-medium">{title}</p>
                <p className="mt-1 text-3xl font-semibold tracking-tight">
                    {value.toLocaleString()}
                </p>
                <p className="mt-3 text-xs leading-5">{caption}</p>
            </CardContent>
        </Card>
    );
}
