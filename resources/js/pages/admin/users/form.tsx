import { useTranslation } from '@/hooks/use-language';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageContainer } from '@/components/admin/page-container';
import { PageHeading } from '@/components/admin/page-heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, store, update } from '@/routes/admin/users';
import { edit as profileEdit } from '@/routes/profile';
import type { AdminUser } from '@/types/admin-user';

type Props = {
    user: AdminUser | null;
    roles: { id: number; name: string; grantable: boolean }[];
    canAssignRoles: boolean;
    canViewUsers: boolean;
    passwordRules: string;
};

export default function UserForm({
    user,
    roles,
    canAssignRoles,
    canViewUsers,
    passwordRules,
}: Props) {
    const t = useTranslation();
    const form = useForm({
        name: user?.name ?? '',
        username: user?.username ?? '',
        email: user?.email ?? '',
        password: '',
        password_confirmation: '',
        roles: user?.roles.map((role) => role.id) ?? [],
    });
    return (
        <>
            <Head title={user ? t('Edit user') : t('Create user')} />
            <PageContainer>
                <PageHeading
                    title={
                        user
                            ? t('Edit {name}', { name: user.name })
                            : t('Create user')
                    }
                    description={t(
                        "Manage account details and access using your team's defined roles.",
                    )}
                />
                <Card className="min-w-0 shadow-none">
                    <CardContent>
                        <form
                            aria-busy={form.processing}
                            className="space-y-8"
                            onSubmit={(event) => {
                                event.preventDefault();
                                form.transform((data) => {
                                    const { roles: assignments, ...fields } =
                                        data;
                                    const previous =
                                        user?.roles
                                            .map((role) => role.id)
                                            .sort((a, b) => a - b) ?? [];
                                    const changed =
                                        JSON.stringify(
                                            [...assignments].sort(
                                                (a, b) => a - b,
                                            ),
                                        ) !== JSON.stringify(previous);
                                    return canAssignRoles && changed
                                        ? { ...fields, roles: assignments }
                                        : fields;
                                });
                                const options = {
                                    preserveScroll: true,
                                    onFinish: () =>
                                        form.reset(
                                            'password',
                                            'password_confirmation',
                                        ),
                                    onSuccess: () => {
                                        if (!user) form.reset();
                                    },
                                };
                                if (user)
                                    form.put(update.url(user.id), options);
                                else form.post(store.url(), options);
                            }}
                        >
                            <div className="grid gap-7 lg:grid-cols-2">
                                <div className="space-y-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="user-name">
                                            {t('Name')}
                                        </Label>
                                        <Input
                                            id="user-name"
                                            required
                                            autoFocus
                                            maxLength={255}
                                            autoComplete="name"
                                            value={form.data.name}
                                            disabled={form.processing}
                                            onChange={(event) =>
                                                form.setData(
                                                    'name',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={!!form.errors.name}
                                            aria-describedby="user-name-error"
                                        />
                                        <InputError
                                            id="user-name-error"
                                            message={form.errors.name}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="user-username">
                                            {t('Username')}
                                        </Label>
                                        <Input
                                            id="user-username"
                                            required
                                            maxLength={100}
                                            autoComplete="username"
                                            value={form.data.username}
                                            disabled={form.processing}
                                            onChange={(event) =>
                                                form.setData(
                                                    'username',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={
                                                !!form.errors.username
                                            }
                                            aria-describedby="user-username-error"
                                        />
                                        <InputError
                                            id="user-username-error"
                                            message={form.errors.username}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="user-email">
                                            {t('Email')}
                                        </Label>
                                        <Input
                                            id="user-email"
                                            required
                                            type="email"
                                            maxLength={255}
                                            autoComplete="off"
                                            value={form.data.email}
                                            disabled={form.processing}
                                            onChange={(event) =>
                                                form.setData(
                                                    'email',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={!!form.errors.email}
                                            aria-describedby="user-email-error"
                                        />
                                        <InputError
                                            id="user-email-error"
                                            message={form.errors.email}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="user-password">
                                            {user
                                                ? t('New password (optional)')
                                                : t('Password')}
                                        </Label>
                                        <PasswordInput
                                            id="user-password"
                                            required={!user}
                                            passwordrules={passwordRules}
                                            autoComplete="new-password"
                                            value={form.data.password}
                                            disabled={form.processing}
                                            onChange={(event) =>
                                                form.setData(
                                                    'password',
                                                    event.target.value,
                                                )
                                            }
                                            aria-invalid={
                                                !!form.errors.password
                                            }
                                            aria-describedby="user-password-help user-password-error"
                                        />
                                        <p
                                            id="user-password-help"
                                            className="text-xs leading-5 text-muted-foreground"
                                        >
                                            {user
                                                ? t(
                                                      'Leave blank to keep the current password.',
                                                  )
                                                : t(
                                                      'Set an initial password using the application password requirements.',
                                                  )}
                                        </p>
                                        <InputError
                                            id="user-password-error"
                                            message={form.errors.password}
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="user-password-confirmation">
                                            {t('Confirm password')}
                                        </Label>
                                        <PasswordInput
                                            id="user-password-confirmation"
                                            required={form.data.password !== ''}
                                            autoComplete="new-password"
                                            value={
                                                form.data.password_confirmation
                                            }
                                            disabled={form.processing}
                                            onChange={(event) =>
                                                form.setData(
                                                    'password_confirmation',
                                                    event.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                </div>
                                <div className="space-y-4 rounded-xl border bg-muted/20 p-5">
                                    <h2 className="font-semibold">
                                        {t('Role assignments')}
                                    </h2>
                                    <p className="text-sm leading-6 text-muted-foreground">
                                        {t(
                                            'Role changes require roles.update. Only roles within your authority can be assigned. The final administrator must keep the Admin role.',
                                        )}
                                    </p>
                                    {canAssignRoles ? (
                                        <fieldset
                                            disabled={form.processing}
                                            className="space-y-4"
                                        >
                                            <legend className="sr-only">
                                                {t('Select user roles')}
                                            </legend>
                                            {roles.length === 0 && (
                                                <p className="text-sm text-muted-foreground">
                                                    {t(
                                                        'No roles are available.',
                                                    )}
                                                </p>
                                            )}
                                            {roles.map((role) => (
                                                <div
                                                    key={role.id}
                                                    className="flex items-center gap-3"
                                                >
                                                    <Checkbox
                                                        id={`user-role-${role.id}`}
                                                        checked={form.data.roles.includes(
                                                            role.id,
                                                        )}
                                                        disabled={
                                                            !role.grantable
                                                        }
                                                        onCheckedChange={(
                                                            checked,
                                                        ) =>
                                                            form.setData(
                                                                'roles',
                                                                checked === true
                                                                    ? [
                                                                          ...form
                                                                              .data
                                                                              .roles,
                                                                          role.id,
                                                                      ]
                                                                    : form.data.roles.filter(
                                                                          (
                                                                              id,
                                                                          ) =>
                                                                              id !==
                                                                              role.id,
                                                                      ),
                                                            )
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={`user-role-${role.id}`}
                                                        className={
                                                            !role.grantable
                                                                ? 'text-muted-foreground'
                                                                : ''
                                                        }
                                                    >
                                                        {role.name}
                                                        {!role.grantable && (
                                                            <span className="ml-2 text-xs">
                                                                {t(
                                                                    'Unavailable',
                                                                )}
                                                            </span>
                                                        )}
                                                    </Label>
                                                </div>
                                            ))}
                                        </fieldset>
                                    ) : (
                                        <>
                                            <p className="text-sm text-muted-foreground">
                                                {t(
                                                    'You can edit account details, but cannot change role assignments.',
                                                )}
                                            </p>
                                            <div className="flex flex-wrap gap-2">
                                                {user?.roles.length ? (
                                                    user.roles.map((role) => (
                                                        <Badge
                                                            key={role.id}
                                                            variant="secondary"
                                                        >
                                                            {role.name}
                                                        </Badge>
                                                    ))
                                                ) : (
                                                    <span className="text-sm text-muted-foreground">
                                                        {t('No roles assigned')}
                                                    </span>
                                                )}
                                            </div>
                                        </>
                                    )}
                                    <InputError message={form.errors.roles} />
                                    {Object.entries(form.errors)
                                        .filter(([key]) =>
                                            key.startsWith('roles.'),
                                        )
                                        .map(([key, message]) => (
                                            <InputError
                                                key={key}
                                                message={message}
                                            />
                                        ))}
                                </div>
                            </div>
                            <div className="flex flex-wrap items-center gap-3 border-t pt-5">
                                <Button disabled={form.processing}>
                                    {form.processing
                                        ? t('Saving...')
                                        : user
                                          ? t('Save changes')
                                          : t('Create user')}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link
                                        href={
                                            canViewUsers
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
                                        {t('User saved.')}
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
UserForm.layout = { breadcrumbs: [] };
