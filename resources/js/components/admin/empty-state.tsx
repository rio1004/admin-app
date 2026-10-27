import type { LucideIcon } from 'lucide-react';
import type { ReactNode } from 'react';

export function EmptyState({
    title,
    description,
    icon: Icon,
    actions,
}: {
    title: string;
    description: string;
    icon: LucideIcon;
    actions?: ReactNode;
}) {
    return (
        <div className="flex flex-col items-center rounded-xl border border-dashed p-8 text-center">
            <span className="mb-4 flex size-12 items-center justify-center rounded-full bg-muted">
                <Icon
                    className="size-5 text-muted-foreground"
                    aria-hidden="true"
                />
            </span>
            <h3 className="font-semibold">{title}</h3>
            <p className="mt-2 max-w-sm text-sm leading-6 text-muted-foreground">
                {description}
            </p>
            {actions && <div className="mt-5">{actions}</div>}
        </div>
    );
}
