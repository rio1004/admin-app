import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from 'react';
import type { PropsWithChildren } from 'react';
import chinese from '@/locales/zh-CN.json';

type Locale = 'en' | 'zh_CN';
type Translate = (
    message: string | undefined,
    values?: Record<string, string | number | undefined>,
) => string;
const translations: Record<string, string> = chinese;
const LanguageContext = createContext<{
    locale: Locale;
    setLocale: (locale: Locale) => void;
    t: Translate;
} | null>(null);

export function LanguageProvider({ children }: PropsWithChildren) {
    const [locale, updateLocale] = useState<Locale>(() =>
        typeof document !== 'undefined' &&
        document.cookie.split('; ').includes('locale=zh_CN')
            ? 'zh_CN'
            : 'en',
    );
    const setLocale = useCallback((value: Locale) => {
        document.cookie = `locale=${value};path=/;max-age=31536000;SameSite=Lax${window.location.protocol === 'https:' ? ';Secure' : ''}`;
        updateLocale(value);
    }, []);
    useEffect(() => {
        document.documentElement.lang = locale === 'zh_CN' ? 'zh-CN' : 'en';
    }, [locale]);
    const t = useCallback<Translate>(
        (message, values = {}) => {
            if (!message) return '';
            const translated =
                locale === 'zh_CN'
                    ? (translations[message] ?? message)
                    : message;
            return translated.replace(/\{(\w+)\}/g, (match, key: string) =>
                values[key] === undefined ? match : String(values[key]),
            );
        },
        [locale],
    );
    const value = useMemo(
        () => ({ locale, setLocale, t }),
        [locale, setLocale, t],
    );
    return (
        <LanguageContext.Provider value={value}>
            {children}
        </LanguageContext.Provider>
    );
}

export function useLanguage() {
    const context = useContext(LanguageContext);
    if (!context) throw new Error('LanguageProvider is required.');
    return context;
}

export function useTranslation() {
    return useLanguage().t;
}
