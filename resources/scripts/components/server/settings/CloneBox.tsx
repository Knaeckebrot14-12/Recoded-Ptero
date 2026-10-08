import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Select from '@/components/elements/Select';
import Label from '@/components/elements/Label';
import { Button } from '@/components/elements/button/index';
import { Dialog } from '@/components/elements/dialog';
import useFlash from '@/plugins/useFlash';
import { CloneInfo, CloneStatus, getCloneInfo, getCloneStatus, startClone } from '@/api/server/clone';

/**
 * Copy all files of this server into another server of the same type. Only shown when the
 * person has at least one more server of this type.
 */
export default () => {
    const { t } = useTranslation('server_clone');
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearAndAddHttpError, clearFlashes } = useFlash();
    const [info, setInfo] = useState<CloneInfo | null>(null);
    const [target, setTarget] = useState('');
    const [confirm, setConfirm] = useState(false);
    const [status, setStatus] = useState<CloneStatus | null>(null);
    const [watching, setWatching] = useState<string | null>(null);

    useEffect(() => {
        getCloneInfo(uuid)
            .then(setInfo)
            .catch(() => setInfo(null));
    }, []);

    // Follow the copy until it is finished.
    useEffect(() => {
        if (!watching) return;
        const timer = setInterval(() => {
            getCloneStatus(uuid, watching)
                .then((s) => {
                    setStatus(s);
                    if (s.state !== 'running') setWatching(null);
                })
                .catch(() => setWatching(null));
        }, 2000);

        return () => clearInterval(timer);
    }, [watching]);

    if (!info || !info.visible) {
        return null;
    }

    const chosen = info.targets.find((s) => s.uuid === target);

    const start = () => {
        setConfirm(false);
        clearFlashes('settings');
        setStatus({ state: 'running', error: null, source: null, keptInTrash: false, files: 0 });
        startClone(uuid, target)
            .then((s) => {
                setStatus(s);
                if (s.state === 'running') setWatching(target);
            })
            .catch((error) => {
                setStatus(null);
                clearAndAddHttpError({ key: 'settings', error });
            });
    };

    const running = status?.state === 'running';

    return (
        <TitledGreyBox title={t('title')} css={tw`mb-6 md:mb-10`}>
            <p css={tw`text-sm text-neutral-300 mb-4`}>{t('description', { hours: info.trashHours })}</p>
            {!info.supported ? (
                <p css={tw`text-xs text-yellow-400`}>{t('unsupported', { version: info.minWingsVersion })}</p>
            ) : (
                <>
                    <Label>{t('target_label')}</Label>
                    <div css={tw`flex gap-2`}>
                        <Select
                            css={tw`flex-1`}
                            value={target}
                            disabled={running}
                            onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setTarget(e.currentTarget.value)}
                        >
                            <option value={''}>{t('choose')}</option>
                            {info.targets.map((s) => (
                                <option key={s.uuid} value={s.uuid} disabled={!s.eligible}>
                                    {s.name}
                                    {s.reason ? ` (${t(`reasons.${s.reason}`)})` : ''}
                                </option>
                            ))}
                        </Select>
                        <Button disabled={!chosen || !chosen.eligible || running} onClick={() => setConfirm(true)}>
                            {t('start')}
                        </Button>
                    </div>
                </>
            )}
            {status && status.state === 'running' && (
                <p css={tw`mt-4 text-sm text-neutral-300`}>{t('running', { files: status.files })}</p>
            )}
            {status && status.state === 'done' && (
                <p css={tw`mt-4 text-sm text-green-400`}>
                    {t('done', { files: status.files, target: chosen?.name || '' })}{' '}
                    {status.keptInTrash ? t('done_kept') : t('done_deleted')}
                </p>
            )}
            {status && status.state === 'failed' && (
                <p css={tw`mt-4 text-sm text-red-400`}>{t('failed', { error: status.error || '?' })}</p>
            )}
            <Dialog.Confirm
                open={confirm}
                onClose={() => setConfirm(false)}
                title={t('confirm_title', { target: chosen?.name || '' })}
                confirm={t('start')}
                onConfirmed={start}
            >
                {t('confirm_body', { target: chosen?.name || '' })}
            </Dialog.Confirm>
        </TitledGreyBox>
    );
};
