import React, { useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Switch from '@/components/elements/Switch';
import Select from '@/components/elements/Select';
import Label from '@/components/elements/Label';
import useFlash from '@/plugins/useFlash';
import { SleepSettings, getSleep, updateSleep } from '@/api/server/sleep';

export default () => {
    const { t } = useTranslation('server_sleep');
    const uuid = ServerContext.useStoreState((state) => state.server.data!.uuid);
    const { clearAndAddHttpError, clearFlashes, addFlash } = useFlash();
    const [settings, setSettings] = useState<SleepSettings | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        getSleep(uuid)
            .then(setSettings)
            .catch(() => setSettings(null));
    }, []);

    if (!settings) {
        return null;
    }

    const save = (enabled: boolean | null, minutes: number | null) => {
        clearFlashes('settings');
        setSaving(true);
        updateSleep(uuid, enabled, minutes)
            .then((updated) => {
                setSettings(updated);
                addFlash({ key: 'settings', type: 'success', message: t('saved') });
            })
            .catch((error) => clearAndAddHttpError({ key: 'settings', error }))
            .then(() => setSaving(false));
    };

    return (
        <TitledGreyBox title={t('title')} css={tw`mb-6 md:mb-10`}>
            <p css={tw`text-sm text-neutral-300 mb-4`}>{t('description')}</p>
            <Switch
                key={`${settings.enabled}`}
                name={'sleep_enabled'}
                label={t('enabled_label')}
                defaultChecked={settings.enabled}
                readOnly={saving}
                onChange={(e) => save(e.currentTarget.checked, settings.custom ? settings.minutes : null)}
            />
            {settings.enabled && (
                <div css={tw`mt-4`}>
                    <Label>{t('minutes_label')}</Label>
                    <Select
                        value={settings.minutes}
                        disabled={saving}
                        onChange={(e: React.ChangeEvent<HTMLSelectElement>) =>
                            save(true, parseInt(e.currentTarget.value, 10))
                        }
                    >
                        {settings.options.map((minutes) => (
                            <option key={minutes} value={minutes}>
                                {t('minutes', { count: minutes })}
                            </option>
                        ))}
                    </Select>
                </div>
            )}
            {settings.custom && (
                <p css={tw`mt-4 text-xs text-neutral-400`}>
                    {t('default_note', {
                        state: settings.defaultEnabled ? t('state_on') : t('state_off'),
                        minutes: settings.defaultMinutes,
                    })}{' '}
                    <a css={tw`text-cyan-400 cursor-pointer`} onClick={() => !saving && save(null, null)}>
                        {t('reset')}
                    </a>
                </p>
            )}
            {!settings.supported && <p css={tw`mt-4 text-xs text-yellow-400`}>{t('unsupported')}</p>}
        </TitledGreyBox>
    );
};
