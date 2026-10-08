import http from '@/api/http';

export interface CloneTarget {
    uuid: string;
    identifier: string;
    name: string;
    eligible: boolean;
    reason: 'other_node' | 'no_permission' | 'unavailable' | null;
}

export interface CloneInfo {
    visible: boolean;
    supported: boolean;
    minWingsVersion: string;
    trashHours: number;
    targets: CloneTarget[];
}

export interface CloneStatus {
    state: 'running' | 'done' | 'failed' | 'none';
    error: string | null;
    source: string | null;
    keptInTrash: boolean;
    files: number;
}

const transformStatus = (data: any): CloneStatus => ({
    state: data.state,
    error: data.error || null,
    source: data.source || null,
    keptInTrash: !!data.kept_in_trash,
    files: data.files || 0,
});

export const getCloneInfo = async (uuid: string): Promise<CloneInfo> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/settings/clone`);

    return {
        visible: data.visible,
        supported: data.supported,
        minWingsVersion: data.min_wings_version,
        trashHours: data.trash_hours,
        targets: data.targets,
    };
};

export const startClone = async (uuid: string, target: string): Promise<CloneStatus> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/settings/clone`, { target });

    return transformStatus(data);
};

export const getCloneStatus = async (uuid: string, target: string): Promise<CloneStatus> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/settings/clone/status`, { params: { target } });

    return transformStatus(data);
};
