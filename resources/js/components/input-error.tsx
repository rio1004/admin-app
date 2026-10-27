import { useTranslation } from '@/hooks/use-language';
import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    const t = useTranslation();
    return message ? (
        <p
            role="alert"
            {...props}
            className={cn('text-sm text-red-600 dark:text-red-400', className)}
        >
            {t(message)}
        </p>
    ) : null;
}
