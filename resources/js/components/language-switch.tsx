import { Languages } from 'lucide-react';
import { useLanguage } from '@/hooks/use-language';

export function LanguageSwitch() {
    const { locale, setLocale } = useLanguage();
    return (
        <div
            className="inline-flex shrink-0 items-center gap-1 rounded-lg bg-muted p-1"
            role="group"
            aria-label="Language / 语言"
        >
            <Languages
                className="mx-1 hidden size-4 text-muted-foreground sm:block"
                aria-hidden="true"
            />
            {(
                [
                    { value: 'en', label: 'English', lang: 'en' },
                    { value: 'zh_CN', label: '简体中文', lang: 'zh-CN' },
                ] as const
            ).map(({ value, label, lang }) => (
                <button
                    key={value}
                    type="button"
                    lang={lang}
                    aria-pressed={locale === value}
                    onClick={() => setLocale(value)}
                    className={`rounded-md px-2 py-1.5 text-xs font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring ${locale === value ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:text-foreground'}`}
                >
                    {label}
                </button>
            ))}
        </div>
    );
}
