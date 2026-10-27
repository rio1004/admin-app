export type Item = {
    id: number;
    name: string;
    description: string | null;
    value: string | null;
    slug: string;
    category_id: number;
    category: { id: number; name: string };
    image_url: string | null;
    link: string | null;
    can: { view: boolean; update: boolean; delete: boolean };
};

export type CategoryOption = { id: number; name: string };
