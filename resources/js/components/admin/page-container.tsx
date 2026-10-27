import type { ReactNode } from 'react';

export function PageContainer({ children }: { children: ReactNode }) {
    return (
        <div className="mx-auto flex w-full max-w-7xl min-w-0 flex-1 flex-col gap-8 p-4 sm:p-8 lg:p-10">
            {children}
        </div>
    );
}
