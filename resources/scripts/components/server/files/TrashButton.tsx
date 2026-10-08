import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCopy, faFileAlt, faFolder, faTrashAlt, faUndo } from '@fortawesome/free-solid-svg-icons';
import { ServerContext } from '@/state/server';
import { Button } from '@/components/elements/button/index';
import { Dialog } from '@/components/elements/dialog';
import Can from '@/components/elements/Can';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import FlashMessageRender from '@/components/FlashMessageRender';
import useFlash from '@/plugins/useFlash';
import useTrashSwr from '@/plugins/useTrashSwr';
import useFileManagerSwr from '@/plugins/useFileManagerSwr';
import { purgeTrash, restoreTrash, Trash, TrashItem } from '@/api/server/files/trash';
import { bytesToString } from '@/lib/formatters';

/** "3 hours ago" / "in 21 hours" in the panel's language. */
const relative = (date: Date, language: string): string => {
    const minutes = Math.round((date.getTime() - Date.now()) / 60000);
    const format = new Intl.RelativeTimeFormat(language, { numeric: 'auto' });
    if (Math.abs(minutes) < 60) return format.format(minutes, 'minute');
    if (Math.abs(minutes) < 60 * 48) return format.format(Math.round(minutes / 60), 'hour');
    return format.format(Math.round(minutes / 1440), 'day');
};

const folderOf = (path: string): string => {
    const parts = path.split('/').filter(Boolean);
    parts.pop();
    return '/' + parts.join('/');
};

export default () => {
    const { t, i18n } = useTranslation('server_files');
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { data: trash, mutate } = useTrashSwr();
    const { mutate: reloadFiles } = useFileManagerSwr();
    const { clearFlashes, clearAndAddHttpError, addFlash } = useFlash();
    const [open, setOpen] = useState(false);
    const [busy, setBusy] = useState(false);
    const [confirmEmpty, setConfirmEmpty] = useState(false);

    const run = (action: () => Promise<Trash>, success: string) => {
        clearFlashes('files:trash');
        setBusy(true);
        action()
            .then((updated) => {
                mutate(updated, false);
                reloadFiles();
                if (success) addFlash({ key: 'files:trash', type: 'success', message: success });
            })
            .catch((error) => {
                mutate();
                clearAndAddHttpError({ key: 'files:trash', error });
            })
            .then(() => setBusy(false));
    };

    const count = trash?.items.length || 0;
    const hours = trash?.hours || 24;

    const title = (item: TrashItem) =>
        item.kind === 'clone' ? t('trash.clone_label', { source: item.label || '?' }) : item.name || item.path;

    return (
        <>
            <Button.Text
                onClick={() => {
                    mutate();
                    setOpen(true);
                }}
                className={'w-full sm:w-auto'}
            >
                <FontAwesomeIcon icon={faTrashAlt} className={'mr-2'} />
                {t('trash.button')}
                {count > 0 && <span className={'ml-2 rounded-full bg-neutral-600 px-2 text-xs'}>{count}</span>}
            </Button.Text>
            <Dialog open={open} onClose={() => setOpen(false)} title={t('trash.title')}>
                <SpinnerOverlay visible={busy} />
                <FlashMessageRender byKey={'files:trash'} className={'mb-2'} />
                <p className={'text-sm text-neutral-300'}>{t('trash.description', { hours })}</p>
                <div className={'mt-4 max-h-[50vh] overflow-y-auto space-y-2'}>
                    {count === 0 ? (
                        <p className={'text-sm text-neutral-400 text-center py-6'}>{t('trash.empty')}</p>
                    ) : (
                        trash!.items.map((item) => (
                            <div key={item.id} className={'flex items-center gap-3 rounded bg-neutral-700 p-3'}>
                                <FontAwesomeIcon
                                    icon={item.kind === 'clone' ? faCopy : item.isFile ? faFileAlt : faFolder}
                                    className={'text-neutral-400'}
                                    fixedWidth
                                />
                                <div className={'min-w-0 flex-1'}>
                                    <p className={'truncate text-sm text-neutral-100'} title={item.path}>
                                        {title(item)}
                                    </p>
                                    <p className={'truncate text-xs text-neutral-400'}>
                                        {item.kind === 'clone' ? t('trash.clone_hint') : folderOf(item.path)}
                                    </p>
                                    <p className={'text-xs text-neutral-500'}>
                                        {t('trash.deleted_ago', { time: relative(item.deletedAt, i18n.language) })} ·{' '}
                                        {t('trash.expires', { time: relative(item.expiresAt, i18n.language) })}
                                        {item.isFile && item.size > 0 && ` · ${bytesToString(item.size)}`}
                                    </p>
                                </div>
                                <Can action={'file.create'}>
                                    <Button.Text
                                        size={Button.Sizes.Small}
                                        disabled={busy}
                                        title={t('trash.restore')}
                                        onClick={() => run(() => restoreTrash(uuid, [item.id]), t('trash.restored'))}
                                    >
                                        <FontAwesomeIcon icon={faUndo} />
                                        <span className={'ml-2 hidden sm:inline'}>{t('trash.restore')}</span>
                                    </Button.Text>
                                </Can>
                                <Can action={'file.delete'}>
                                    <Button.Danger
                                        size={Button.Sizes.Small}
                                        variant={Button.Variants.Secondary}
                                        disabled={busy}
                                        title={t('trash.delete')}
                                        onClick={() => run(() => purgeTrash(uuid, [item.id]), '')}
                                    >
                                        <FontAwesomeIcon icon={faTrashAlt} />
                                    </Button.Danger>
                                </Can>
                            </div>
                        ))
                    )}
                </div>
                <Dialog.Footer>
                    {count > 0 && (
                        <Can action={'file.delete'}>
                            <Button.Danger
                                variant={Button.Variants.Secondary}
                                disabled={busy}
                                onClick={() => setConfirmEmpty(true)}
                            >
                                {t('trash.empty_all')}
                            </Button.Danger>
                        </Can>
                    )}
                    <Button.Text onClick={() => setOpen(false)}>{t('trash.close')}</Button.Text>
                </Dialog.Footer>
            </Dialog>
            <Dialog.Confirm
                open={confirmEmpty}
                onClose={() => setConfirmEmpty(false)}
                title={t('trash.empty_confirm_title')}
                confirm={t('trash.empty_all')}
                onConfirmed={() => {
                    setConfirmEmpty(false);
                    run(() => purgeTrash(uuid, null), t('trash.emptied'));
                }}
            >
                {t('trash.empty_confirm_body')}
            </Dialog.Confirm>
        </>
    );
};
