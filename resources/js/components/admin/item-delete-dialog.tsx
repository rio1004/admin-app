import { useTranslation } from '@/hooks/use-language';
import { useForm } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy } from '@/routes/admin/items';

export function ItemDeleteDialog({
    item,
    onClose,
}: {
    item: { id: number; name: string } | null;
    onClose: () => void;
}) {
    const t = useTranslation();
    const form = useForm({});
    return (
        <Dialog
            open={item !== null}
            onOpenChange={(open) => {
                if (!open && !form.processing) {
                    form.clearErrors();
                    onClose();
                }
            }}
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {t('Delete')} {item?.name}?
                    </DialogTitle>
                    <DialogDescription>
                        {t(
                            'This permanently deletes the item and removes its stored image. This action cannot be undone.',
                        )}
                    </DialogDescription>
                </DialogHeader>
                <InputError
                    message={(form.errors as Record<string, string>).item}
                />
                <DialogFooter>
                    <Button
                        variant="outline"
                        disabled={form.processing}
                        onClick={() => {
                            form.clearErrors();
                            onClose();
                        }}
                    >
                        {t('Cancel')}
                    </Button>
                    <Button
                        variant="destructive"
                        disabled={form.processing}
                        onClick={() => {
                            if (item)
                                form.delete(destroy.url(item.id), {
                                    preserveScroll: true,
                                    onSuccess: onClose,
                                });
                        }}
                    >
                        {form.processing ? t('Deleting...') : t('Delete item')}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
