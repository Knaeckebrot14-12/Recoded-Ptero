import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { useHistory } from 'react-router-dom';
import { Formik, FormikHelpers, Form, useField } from 'formik';
import { object, number, string } from 'yup';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCoins, faTrashAlt } from '@fortawesome/free-solid-svg-icons';
import PageContentBlock from '@/components/elements/PageContentBlock';
import Field from '@/components/elements/Field';
import Select from '@/components/elements/Select';
import Button from '@/components/elements/Button';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Spinner from '@/components/elements/Spinner';
import { Dialog } from '@/components/elements/dialog';
import useFlash from '@/plugins/useFlash';
import getSelfServiceServerOptions, { SelfServiceServerOptions } from '@/api/getSelfServiceServerOptions';
import createSelfServiceServer from '@/api/createSelfServiceServer';
import deleteSelfServiceServer from '@/api/deleteSelfServiceServer';

interface Values {
    name: string;
    eggId: string;
    nodeId: string;
    memory: number;
    disk: number;
    cpu: number;
    backupLimit: number;
}

const ResourceRow = ({ label, used, limit, unit }: { label: string; used: number; limit: number; unit: string }) => {
    const percent = limit > 0 ? Math.min(100, Math.round((used / limit) * 100)) : 0;

    return (
        <div css={tw`mb-4 last:mb-0`}>
            <div css={tw`flex justify-between text-sm text-neutral-300 mb-1`}>
                <span>{label}</span>
                <span>
                    {used} / {limit} {unit}
                </span>
            </div>
            <div css={tw`w-full bg-neutral-900 rounded h-2 overflow-hidden`}>
                <div css={tw`h-2 bg-primary-500`} style={{ width: `${percent}%` }} />
            </div>
        </div>
    );
};

// A bare native <select> wired to Formik — the Field component only renders
// text-style inputs, and a long egg/node list is clearer as a real dropdown.
const FormikNativeSelect = ({
    name,
    disabled,
    children,
}: {
    name: string;
    disabled?: boolean;
    children: React.ReactNode;
}) => {
    const [field] = useField(name);

    return (
        <Select {...field} disabled={disabled}>
            {children}
        </Select>
    );
};

export default () => {
    const { t } = useTranslation('create_server');
    const history = useHistory();

    const formatMinutes = (seconds: number): string => {
        const minutes = Math.ceil(seconds / 60);
        return t('minutes', { count: minutes });
    };

    const formatPaidUntil = (iso: string): string => {
        const days = Math.ceil((new Date(iso).getTime() - Date.now()) / (1000 * 60 * 60 * 24));
        if (days <= 0) return t('renewal_overdue');
        return t('renews_in', { count: days });
    };
    const [complete, setComplete] = useState(false);
    const [options, setOptions] = useState<SelfServiceServerOptions | null>(null);
    const [pendingDelete, setPendingDelete] = useState<string | null>(null);
    const [deleting, setDeleting] = useState<Record<string, boolean>>({});
    const { clearFlashes, addFlash, clearAndAddHttpError } = useFlash();

    const refresh = () => {
        getSelfServiceServerOptions()
            .then(setOptions)
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'create-server', error });
            });
    };

    useEffect(() => {
        clearFlashes('create-server');
        refresh();
    }, []);

    const confirmDelete = () => {
        if (!pendingDelete) return;
        const identifier = pendingDelete;

        setPendingDelete(null);
        setDeleting((state) => ({ ...state, [identifier]: true }));
        clearFlashes('create-server');

        deleteSelfServiceServer(identifier)
            .then(({ refund }) => {
                addFlash({
                    key: 'create-server',
                    type: 'success',
                    message: refund > 0 ? t('delete_success_refund', { refund }) : t('delete_success'),
                });
                refresh();
            })
            .catch((error) => {
                console.error(error);
                clearAndAddHttpError({ key: 'create-server', error });
            })
            .then(() => setDeleting((state) => ({ ...state, [identifier]: false })));
    };

    if (!options) {
        return (
            <PageContentBlock title={t('title')} showFlashKey={'create-server'}>
                <Spinner centered size={'large'} />
            </PageContentBlock>
        );
    }

    const remaining = {
        memory: Math.max(0, options.limits.memory - options.used.memory),
        disk: Math.max(0, options.limits.disk - options.used.disk),
        cpu: Math.max(0, options.limits.cpu - options.used.cpu),
        backups: Math.max(0, options.limits.backups - options.used.backups),
    };
    const slotsRemaining = Math.max(0, options.limits.slots - options.used.slots);
    const onCooldown = options.cooldownSecondsRemaining > 0;
    const firstEgg = options.nests.find((n) => n.eggs.length > 0)?.eggs[0];

    const onSubmit = (values: Values, { setSubmitting }: FormikHelpers<Values>) => {
        clearFlashes('create-server');
        setComplete(false);

        createSelfServiceServer({
            name: values.name,
            eggId: Number(values.eggId),
            nodeId: values.nodeId === 'auto' ? null : Number(values.nodeId),
            memory: values.memory,
            disk: values.disk,
            cpu: values.cpu,
            backupLimit: values.backupLimit,
        })
            .then((identifier) => {
                setComplete(true);
                setTimeout(() => history.push(`/server/${identifier}`), 1000);
            })
            .catch((error) => {
                console.error(error);
                setSubmitting(false);
                clearAndAddHttpError({ key: 'create-server', error });
            });
    };

    return (
        <PageContentBlock title={t('title')} showFlashKey={'create-server'}>
            <h1 css={tw`text-5xl mb-8`}>{t('title')}</h1>
            <Dialog.Confirm
                open={pendingDelete !== null}
                onClose={() => setPendingDelete(null)}
                title={t('delete_dialog.title')}
                confirm={t('delete_dialog.confirm')}
                onConfirmed={confirmDelete}
            >
                {t('delete_dialog.body')}
            </Dialog.Confirm>
            {options.servers.length > 0 && (
                <TitledGreyBox title={t('your_servers')} css={tw`mb-8`}>
                    {options.servers.map((server) => (
                        <GreyRowBox key={server.identifier} $hoverable={false} css={tw`mb-2 last:mb-0`}>
                            <div css={tw`flex-1 overflow-hidden`}>
                                <p css={tw`text-sm truncate`}>{server.name}</p>
                                <p css={tw`text-xs text-neutral-400`}>
                                    {server.memory} MiB / {server.disk} MiB / {server.cpu}% CPU
                                </p>
                                {server.paidWithCoinsUntil && (
                                    <p css={tw`text-xs text-yellow-400 mt-1`}>
                                        <FontAwesomeIcon icon={faCoins} css={tw`mr-1`} />
                                        {t('bought_with_coins', { status: formatPaidUntil(server.paidWithCoinsUntil) })}
                                    </p>
                                )}
                            </div>
                            <Button
                                size={'small'}
                                color={'red'}
                                isSecondary
                                isLoading={!!deleting[server.identifier]}
                                onClick={() => setPendingDelete(server.identifier)}
                            >
                                <FontAwesomeIcon icon={faTrashAlt} fixedWidth />
                            </Button>
                        </GreyRowBox>
                    ))}
                </TitledGreyBox>
            )}
            {options.suspended ? (
                <TitledGreyBox title={t('account_suspended.title')}>
                    <p css={tw`text-sm text-neutral-300`}>{t('account_suspended.body')}</p>
                </TitledGreyBox>
            ) : slotsRemaining <= 0 ? (
                <TitledGreyBox title={t('no_slots.title')}>
                    <p css={tw`text-sm text-neutral-300`}>{t('no_slots.body', { slots: options.limits.slots })}</p>
                </TitledGreyBox>
            ) : onCooldown ? (
                <TitledGreyBox title={t('cooldown.title')}>
                    <p css={tw`text-sm text-neutral-300`}>
                        {t('cooldown.body', { time: formatMinutes(options.cooldownSecondsRemaining) })}
                    </p>
                </TitledGreyBox>
            ) : (
                <div css={tw`md:flex`}>
                    <TitledGreyBox title={t('resource_pool.title')} css={tw`md:w-1/3 mb-6 md:mb-0 md:mr-8`}>
                        <ResourceRow
                            label={t('resource_pool.memory')}
                            used={options.used.memory}
                            limit={options.limits.memory}
                            unit={'MiB'}
                        />
                        <ResourceRow
                            label={t('resource_pool.disk')}
                            used={options.used.disk}
                            limit={options.limits.disk}
                            unit={'MiB'}
                        />
                        <ResourceRow
                            label={t('resource_pool.cpu')}
                            used={options.used.cpu}
                            limit={options.limits.cpu}
                            unit={'%'}
                        />
                        <ResourceRow
                            label={t('resource_pool.backups')}
                            used={options.used.backups}
                            limit={options.limits.backups}
                            unit={''}
                        />
                        <p css={tw`text-xs text-neutral-400 mt-4`}>
                            {t('resource_pool.slots_remaining', {
                                remaining: slotsRemaining,
                                total: options.limits.slots,
                            })}
                        </p>
                    </TitledGreyBox>
                    <div css={tw`md:w-2/3`}>
                        <Formik
                            onSubmit={onSubmit}
                            initialValues={{
                                name: '',
                                eggId: firstEgg ? String(firstEgg.id) : '',
                                nodeId: 'auto',
                                memory: Math.min(2048, remaining.memory),
                                disk: Math.min(5120, remaining.disk),
                                cpu: Math.min(100, remaining.cpu),
                                backupLimit: Math.min(1, remaining.backups),
                            }}
                            validationSchema={object().shape({
                                name: string().required(t('validation.name_required')).min(1).max(191),
                                eggId: string().required(t('validation.egg_required')),
                                nodeId: string().required(t('validation.node_required')),
                                memory: number()
                                    .min(128, t('validation.memory_min'))
                                    .max(remaining.memory, t('validation.memory_max', { remaining: remaining.memory })),
                                disk: number()
                                    .min(128, t('validation.disk_min'))
                                    .max(remaining.disk, t('validation.disk_max', { remaining: remaining.disk })),
                                cpu: number()
                                    .min(25, t('validation.cpu_min'))
                                    .max(remaining.cpu, t('validation.cpu_max', { remaining: remaining.cpu })),
                                backupLimit: number()
                                    .min(0)
                                    .max(
                                        remaining.backups,
                                        t('validation.backup_max', { remaining: remaining.backups })
                                    ),
                            })}
                        >
                            {({ isSubmitting }) => (
                                <Form>
                                    <TitledGreyBox title={t('server_details.title')}>
                                        <Field
                                            id={'name'}
                                            name={'name'}
                                            type={'text'}
                                            label={t('server_details.name_label')}
                                            placeholder={t('server_details.name_placeholder')}
                                            disabled={isSubmitting || complete}
                                        />
                                        <div css={tw`grid grid-cols-2 gap-4 mt-4`}>
                                            <div>
                                                <label css={tw`block text-xs uppercase text-neutral-300 mb-1`}>
                                                    {t('server_details.egg_label')}
                                                </label>
                                                <FormikNativeSelect name={'eggId'} disabled={isSubmitting || complete}>
                                                    {options.nests.map((nest) => (
                                                        <optgroup key={nest.id} label={nest.name}>
                                                            {nest.eggs.map((egg) => (
                                                                <option key={egg.id} value={egg.id}>
                                                                    {egg.name}
                                                                </option>
                                                            ))}
                                                        </optgroup>
                                                    ))}
                                                </FormikNativeSelect>
                                            </div>
                                            <div>
                                                <label css={tw`block text-xs uppercase text-neutral-300 mb-1`}>
                                                    {t('server_details.node_label')}
                                                </label>
                                                <FormikNativeSelect name={'nodeId'} disabled={isSubmitting || complete}>
                                                    <option value={'auto'}>{t('server_details.node_auto')}</option>
                                                    {options.nodes.map((node) => (
                                                        <option key={node.id} value={node.id}>
                                                            {node.name} (
                                                            {node.maximumServers === null
                                                                ? t('server_details.node_servers', {
                                                                      count: node.servers,
                                                                  })
                                                                : `${node.servers}/${node.maximumServers}`}
                                                            )
                                                        </option>
                                                    ))}
                                                </FormikNativeSelect>
                                            </div>
                                        </div>
                                        <div css={tw`grid grid-cols-2 gap-4 mt-4`}>
                                            <Field
                                                id={'memory'}
                                                name={'memory'}
                                                type={'number'}
                                                label={t('server_details.memory_label')}
                                                disabled={isSubmitting || complete}
                                            />
                                            <Field
                                                id={'disk'}
                                                name={'disk'}
                                                type={'number'}
                                                label={t('server_details.disk_label')}
                                                disabled={isSubmitting || complete}
                                            />
                                        </div>
                                        <div css={tw`grid grid-cols-2 gap-4 mt-4`}>
                                            <Field
                                                id={'cpu'}
                                                name={'cpu'}
                                                type={'number'}
                                                label={t('server_details.cpu_label')}
                                                disabled={isSubmitting || complete}
                                            />
                                            <Field
                                                id={'backupLimit'}
                                                name={'backupLimit'}
                                                type={'number'}
                                                label={t('server_details.backups_label')}
                                                disabled={isSubmitting || complete}
                                            />
                                        </div>
                                        <div css={tw`mt-6`}>
                                            <Button
                                                type={'submit'}
                                                size={'xlarge'}
                                                isLoading={isSubmitting}
                                                disabled={isSubmitting || complete}
                                            >
                                                {complete ? t('redirecting') : t('create_button')}
                                            </Button>
                                        </div>
                                    </TitledGreyBox>
                                </Form>
                            )}
                        </Formik>
                    </div>
                </div>
            )}
        </PageContentBlock>
    );
};
