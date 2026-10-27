import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';

export function useNavigationPending(): boolean {
    const [pending, setPending] = useState(false);
    useEffect(() => {
        const start = router.on('start', () => setPending(true));
        const finish = router.on('finish', () => setPending(false));
        return () => {
            start();
            finish();
        };
    }, []);
    return pending;
}
