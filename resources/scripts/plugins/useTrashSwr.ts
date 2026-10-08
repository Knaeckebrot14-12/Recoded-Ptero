import useSWR from 'swr';
import { ServerContext } from '@/state/server';
import { getTrash, Trash } from '@/api/server/files/trash';

export const getTrashSwrKey = (uuid: string): string => `${uuid}:files:trash`;

/** The server's trash, shared by the file manager's trash button and its delete dialogs. */
export default () => {
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);

    return useSWR<Trash>(getTrashSwrKey(uuid), () => getTrash(uuid), {
        revalidateOnFocus: false,
        errorRetryCount: 1,
    });
};
