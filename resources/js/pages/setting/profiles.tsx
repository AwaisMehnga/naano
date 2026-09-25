import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { ApiError, http, sharedApi } from '@/lib/api';
import type { ProfileSummary } from '@/types/auth';

type ProfilesPayload = {
    profiles: ProfileSummary[];
    active_profile: string | null;
    can_create: string[];
};

export default function ProfilesPage() {
    const [data, setData] = useState<ProfilesPayload | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    async function load() {
        try {
            const { data: payload } = await http.get<ProfilesPayload>(
                sharedApi.profiles,
            );
            setData(payload);
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load profiles.',
            );
        }
    }

    useEffect(() => {
        void load();
    }, []);

    async function switchTo(type: string) {
        setBusy(true);
        setError(null);

        try {
            const { data: payload } = await http.patch<ProfilesPayload>(
                sharedApi.profilesActive,
                { type },
            );
            setData(payload);

            if (window.Naano?.user) {
                window.Naano.user.active_profile = payload.active_profile;
                window.Naano.user.role = payload.active_profile;
                window.Naano.user.profiles = payload.profiles;
                window.Naano.user.can_create_profiles = payload.can_create;
            }

            toast.success(
                type === 'creator'
                    ? 'Switched to creator'
                    : 'Switched to company',
            );

            window.location.href = type === 'creator' ? '/creator' : '/company';
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not switch profile.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function create(type: 'creator' | 'company') {
        setBusy(true);
        setError(null);

        try {
            const url =
                type === 'creator'
                    ? sharedApi.profilesCreator
                    : sharedApi.profilesCompany;
            const { data: payload } = await http.post<ProfilesPayload>(url);
            setData(payload);
            toast.success(
                type === 'creator'
                    ? 'Creator profile created'
                    : 'Company profile created',
            );
            window.location.href =
                type === 'creator'
                    ? '/onboarding/creator'
                    : '/onboarding/company';
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not create profile.',
            );
            setBusy(false);
        }
    }

    return (
        <div className="max-w-xl">
            <Heading
                title="Profiles"
                description="Use one account for creator and company. Switch anytime."
            />
            <InputError message={error ?? undefined} />
            {data && (
                <div className="grid gap-4">
                    {data.profiles.map((profile) => (
                        <div
                            key={profile.type}
                            className="flex items-center justify-between gap-3 rounded-lg border border-border p-4"
                        >
                            <div>
                                <p className="font-medium capitalize">
                                    {profile.type}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {profile.label}
                                    {profile.onboarded
                                        ? ''
                                        : ' · Finish onboarding'}
                                    {data.active_profile === profile.type
                                        ? ' · Active'
                                        : ''}
                                </p>
                            </div>
                            {data.active_profile !== profile.type && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    disabled={busy}
                                    onClick={() => void switchTo(profile.type)}
                                >
                                    Switch
                                </Button>
                            )}
                        </div>
                    ))}
                    {data.can_create.includes('creator') && (
                        <Button
                            type="button"
                            disabled={busy}
                            onClick={() => void create('creator')}
                        >
                            Add creator profile
                        </Button>
                    )}
                    {data.can_create.includes('company') && (
                        <Button
                            type="button"
                            disabled={busy}
                            onClick={() => void create('company')}
                        >
                            Add company profile
                        </Button>
                    )}
                </div>
            )}
        </div>
    );
}
