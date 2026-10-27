import { useTranslation } from '@/hooks/use-language';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/admin/roles';
import { edit as profileEdit } from '@/routes/profile';

type Props = {
    role: { id: number; name: string; permissions: string[] } | null;
    permissions: { name: string; grantable: boolean }[];
    canViewRoles: boolean;
};

export default function RoleForm({ role, permissions, canViewRoles }: Props) {
    const t = useTranslation();
    const form = useForm({
        name: role?.name ?? '',
        permissions: role?.permissions ?? [],
    });
    const groups = [
        ...new Set(
            permissions.map((permission) => permission.name.split('.')[0]),
        ),
    ];
    return (
        <>
            <Head title={role ? t('Edit role') : t('Create role')} />
            <PageContainer>
                <PageHeading
                    title={
                        role
                            ? t('Edit {name}', { name: role.name })
                            : t('Create role')
                    }
                    description={t(
                        'Choose a name and select the permissions this role should grant. You can only manage permissions you hold.',
                    )}
                />
                <Card className="min-w-0 shadow-none">
                    <CardContent>
                        <form
                            aria-busy={form.processing}
                            className="space-y-8"
                            onSubmit={(event) => {
                                event.preventDefault();
                                if (role)
                                    form.put(update.url(role.id), {
                                        preserveScroll: true,
                                    });
                                else form.post(store.url());
                            }}
                        >
                            <div className="max-w-lg space-y-2">
                                <Label htmlFor="role-name">
                                    {t('Role name')}
                                </Label>
                                <Input
                                    id="role-name"
                                    name="name"
                                    autoFocus
                                    required
                                    disabled={form.processing}
                                    maxLength={100}
                                    value={form.data.name}
                                    onChange={(event) =>
                                        form.setData('name', event.target.value)
                                    }
                                    aria-invalid={!!form.errors.name}
                                    aria-describedby={
                                        form.errors.name
                                            ? 'role-name-error'
                                            : undefined
                                    }
                                />
                                <InputError
                                    id="role-name-error"
                                    message={form.errors.name}
                                />
                            </div>
                            <div>
                                <h2 className="mb-2 font-semibold">
                                    {t('Permissions')}
                                </h2>
                                <p className="mb-5 text-sm text-muted-foreground">
                                    {t(
                                        'Admin access opens the workspace. Each resource also needs its own action permissions. Unavailable permissions are disabled.',
                                    )}
                                </p>
                                <InputError message={form.errors.permissions} />
                                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                                    {groups.map((group) => (
                                        <fieldset
                                            key={group}
                                            className="space-y-4 rounded-xl border p-5"
                                        >
                                            <legend className="px-2 text-sm font-semibold capitalize">
                                                {group}
                                            </legend>
                                            {permissions
                                                .filter((permission) =>
                                                    permission.name.startsWith(
                                                        `${group}.`,
                                                    ),
                                                )
                                                .map((permission) => (
                                                    <div
                                                        key={permission.name}
                                                        className="flex items-center gap-3"
                                                    >
                                                        <Checkbox
                                                            id={permission.name}
                                                            checked={form.data.permissions.includes(
                                                                permission.name,
                                                            )}
                                                            disabled={
                                                                !permission.grantable ||
                                                                form.processing
                                                            }
                                                            onCheckedChange={(
                                                                checked,
                                                            ) =>
                                                                form.setData(
                                                                    'permissions',
                                                                    checked ===
                                                                        true
                                                                        ? [
                                                                              ...form
                                                                                  .data
                                                                                  .permissions,
                                                                              permission.name,
                                                                          ]
                                                                        : form.data.permissions.filter(
                                                                              (
                                                                                  name,
                                                                              ) =>
                                                                                  name !==
                                                                                  permission.name,
                                                                          ),
                                                                )
                                                            }
                                                        />
                                                        <Label
                                                            htmlFor={
                                                                permission.name
                                                            }
                                                            className={
                                                                !permission.grantable
                                                                    ? 'text-muted-foreground'
                                                                    : ''
                                                            }
                                                        >
                                                            {permission.name}
                                                        </Label>
                                                    </div>
                                                ))}
                                        </fieldset>
                                    ))}
                                </div>
                                {Object.entries(form.errors)
                                    .filter(([key]) =>
                                        key.startsWith('permissions.'),
                                    )
                                    .map(([key, message]) => (
                                        <InputError
                                            key={key}
                                            message={message}
                                        />
                                    ))}
                            </div>
                            <div className="flex flex-wrap items-center gap-3 border-t pt-5">
                                <Button disabled={form.processing}>
                                    {form.processing
                                        ? t('Saving...')
                                        : role
                                          ? t('Save changes')
                                          : t('Create role')}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={
                                            canViewRoles
                                                ? index()
                                                : profileEdit()
                                        }
                                    >
                                        {t('Cancel')}
                                    </Link>
                                </Button>
                                {form.recentlySuccessful && (
                                    <p
                                        role="status"
                                        className="text-sm text-muted-foreground"
                                    >
                                        {t('Role saved.')}
                                    </p>
                                )}
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </PageContainer>
        </>
    );
}
RoleForm.layout = { breadcrumbs: [] };
