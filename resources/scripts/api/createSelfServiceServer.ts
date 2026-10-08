import http from '@/api/http';

export interface CreateSelfServiceServerData {
    name: string;
    eggId: number;
    nodeId: number | null;
    memory: number;
    disk: number;
    cpu: number;
    backupLimit: number;
}

export default (data: CreateSelfServiceServerData): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/self-service/servers', {
            name: data.name,
            egg_id: data.eggId,
            node_id: data.nodeId,
            memory: data.memory,
            disk: data.disk,
            cpu: data.cpu,
            backup_limit: data.backupLimit,
        })
            .then(({ data }) => resolve(data.data.identifier))
            .catch(reject);
    });
};
