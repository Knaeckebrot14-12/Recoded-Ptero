import React from 'react';
import { useTranslation } from 'react-i18next';
import useTrashSwr from '@/plugins/useTrashSwr';

/**
 * Renders with the hours deleted files stay in the trash. A component of its own, so the trash
 * is only fetched while a delete dialog is open and not once per file row.
 */
export const WithTrashHours = ({ children }: { children: (hours: number) => React.ReactNode }) => {
    const hours = useTrashSwr().data?.hours || 24;

    return <>{children(hours)}</>;
};

/** The "delete for good right away" choice in the file manager's delete dialogs. */
export default ({ checked, onChange }: { checked: boolean; onChange: (checked: boolean) => void }) => {
    const { t } = useTranslation('server_files');

    return (
        <label className={'mt-4 flex cursor-pointer items-center gap-2 text-sm text-neutral-300'}>
            <input
                type={'checkbox'}
                className={'rounded border-neutral-500 bg-neutral-800 text-red-500'}
                checked={checked}
                onChange={(e) => onChange(e.currentTarget.checked)}
            />
            {t('trash.permanent_label')}
        </label>
    );
};
