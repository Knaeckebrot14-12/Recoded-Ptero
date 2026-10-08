import http from '@/api/http';

/** Files go into the server's trash unless "permanent" is set. */
export default (uuid: string, directory: string, files: string[], permanent = false): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/delete`, { root: directory, files, permanent })
            .then(() => resolve())
            .catch(reject);
    });
};
