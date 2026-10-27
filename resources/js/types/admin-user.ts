export type AdminUser = {
    id: number;
    name: string;
    username: string;
    email: string;
    roles: { id: number; name: string }[];
    is_self: boolean;
    can: { update: boolean; delete: boolean };
};
