import http from '@/api/http';

export interface SleepSettings {
    enabled: boolean;
    minutes: number;
    custom: boolean;
    defaultEnabled: boolean;
    defaultMinutes: number;
    options: number[];
    supported: boolean;
}

const transform = (data: any): SleepSettings => ({
    enabled: data.enabled,
    minutes: data.minutes,
    custom: data.custom,
    defaultEnabled: data.default_enabled,
    defaultMinutes: data.default_minutes,
    options: data.options,
    supported: data.supported,
});

export const getSleep = async (uuid: string): Promise<SleepSettings> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/settings/sleep`);

    return transform(data);
};

/** null for either value means "follow the panel's default". */
export const updateSleep = async (
    uuid: string,
    enabled: boolean | null,
    minutes: number | null
): Promise<SleepSettings> => {
    const { data } = await http.put(`/api/client/servers/${uuid}/settings/sleep`, { enabled, minutes });

    return transform(data);
};
