import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { ApiError, http, sharedApi } from '@/lib/api';

type Preferences = {
    email_invites: boolean;
    email_applications: boolean;
    email_campaign_updates: boolean;
    email_messages: boolean;
};

const toggles: { key: keyof Preferences; label: string }[] = [
    { key: 'email_invites', label: 'Campaign invites' },
    { key: 'email_applications', label: 'Applications' },
    { key: 'email_campaign_updates', label: 'Campaign updates' },
    { key: 'email_messages', label: 'Messages' },
];

export default function NotificationSettingsPage() {
    const [prefs, setPrefs] = useState<Preferences | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        http.get<Preferences>(sharedApi.notificationPreferences)
            .then(({ data }) => setPrefs(data))
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load notification preferences.',
                );
            });
    }, []);

    async function save() {
        if (!prefs) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            const { data } = await http.put<Preferences>(
                sharedApi.notificationPreferences,
                prefs,
            );
            setPrefs(data);
            toast.success('Preferences saved');
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save preferences.',
            );
        } finally {
            setSaving(false);
        }
    }

    return (
        <div className="max-w-xl">
            <Heading
                title="Notifications"
                description="Choose which updates also arrive by email. In-app alerts stay on."
            />
            <InputError message={error ?? undefined} />
            {prefs && (
                <div className="grid gap-4">
                    {toggles.map((toggle) => (
                        <div key={toggle.key} className="flex items-center gap-3">
                            <Checkbox
                                id={toggle.key}
                                checked={prefs[toggle.key]}
                                onCheckedChange={(checked) =>
                                    setPrefs((current) =>
                                        current
                                            ? {
                                                  ...current,
                                                  [toggle.key]: checked === true,
                                              }
                                            : current,
                                    )
                                }
                            />
                            <Label htmlFor={toggle.key}>{toggle.label}</Label>
                        </div>
                    ))}
                    <Button
                        type="button"
                        className="w-fit"
                        disabled={saving}
                        onClick={() => void save()}
                    >
                        Save
                    </Button>
                </div>
            )}
        </div>
    );
}
