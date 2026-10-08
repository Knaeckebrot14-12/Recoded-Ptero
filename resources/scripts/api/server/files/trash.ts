import http from '@/api/http';

export interface TrashItem {
    id: number;
    kind: 'file' | 'clone';
    name: string | null;
    path: string;
    isFile: boolean;
    size: number;
    label: string | null;
    deletedAt: Date;
    expiresAt: Date;
}

export interface Trash {
    hours: number;
    items: TrashItem[];
}

const transform = (data: any): Trash => ({
    hours: data.hours,
    items: (data.items || []).map((item: any) => ({
        id: item.id,
        kind: item.kind,
        name: item.name,
        path: item.path,
        isFile: item.is_file,
        size: item.size,
        label: item.label,
        deletedAt: new Date(item.deleted_at),
        expiresAt: new Date(item.expires_at),
    })),
});

export const getTrash = async (uuid: string): Promise<Trash> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/files/trash`);

    return transform(data);
};

export const restoreTrash = async (uuid: string, ids: number[]): Promise<Trash> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/files/trash/restore`, { ids });

    return transform(data);
};

/** Deletes the given entries for good, or everything when ids is null. */
export const purgeTrash = async (uuid: string, ids: number[] | null): Promise<Trash> => {
    const { data } = await http.post(
        `/api/client/servers/${uuid}/files/trash/delete`,
        ids === null ? { all: true } : { ids }
    );

    return transform(data);
};
