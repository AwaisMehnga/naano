import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import { SoftCard } from '@/components/ds/soft-card';
import { EditableSettingRow } from '@/components/editable-setting-row';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { ProfileImageField } from '@/components/profile-image-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { api, ApiError, companyApi } from '@/lib/api';
import { setCurrentUserAvatar } from '@/lib/current-user';
import { countries } from '@/lib/lookups';

type CompanyProfile = {
    id: number;
    name: string | null;
    website: string | null;
    logo_url: string | null;
    billing_email: string | null;
    country: string | null;
    value_proposition: string | null;
    can_manage_money: boolean;
};

type FieldKey =
    | 'name'
    | 'website'
    | 'country'
    | 'value_proposition'
    | 'billing_email'
    | null;

function countryLabel(code: string | null): string | null {
    if (!code) {
        return null;
    }

    return countries.find((item) => item.value === code)?.label ?? code;
}

export default function CompanyProfilePage() {
    const [profile, setProfile] = useState<CompanyProfile | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [editing, setEditing] = useState<FieldKey>(null);
    const [draft, setDraft] = useState('');
    const [logo, setLogo] = useState<File | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);

    useEffect(() => {
        api<CompanyProfile>(companyApi.profile)
            .then((data) => {
                setProfile(data);
            })
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load profile.',
                );
            });
    }, []);

    function startEdit(field: Exclude<FieldKey, null>) {
        if (!profile) {
            return;
        }

        setEditing(field);
        setError(null);
        setDraft(profile[field] ?? '');
    }

    function cancelEdit() {
        setEditing(null);
        setDraft('');
    }

    async function patchField(fields: Record<string, string>) {
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        for (const [key, value] of Object.entries(fields)) {
            formData.append(key, value);
        }

        return api<CompanyProfile>(companyApi.profile, {
            method: 'POST',
            body: formData,
        });
    }

    async function saveField(field: Exclude<FieldKey, null>) {
        if (!profile || !field) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            const updated = await patchField({ [field]: draft });
            setProfile(updated);
            setEditing(null);
            toast.success('Saved.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save profile.',
            );
        } finally {
            setSaving(false);
        }
    }

    async function saveLogo() {
        if (!logo) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('logo', logo);
            const updated = await api<CompanyProfile>(companyApi.profile, {
                method: 'POST',
                body: formData,
            });
            setProfile(updated);
            setLogo(null);
            setCurrentUserAvatar(updated.logo_url);
            toast.success('Logo saved.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save logo.',
            );
        } finally {
            setSaving(false);
        }
    }

    async function removeLogo() {
        setRemoving(true);
        setError(null);
        try {
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('remove_logo', '1');
            const updated = await api<CompanyProfile>(companyApi.profile, {
                method: 'POST',
                body: formData,
            });
            setProfile(updated);
            setLogo(null);
            setCurrentUserAvatar(null);
            toast.success('Logo removed.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not remove logo.',
            );
        } finally {
            setRemoving(false);
        }
    }

    if (!profile) {
        return (
            <p className="text-sm text-muted-foreground">{error ?? 'Loading…'}</p>
        );
    }

    return (
        <div className="space-y-6">
            <Heading
                title="Company profile"
                description="How this workspace appears on Naano."
            />

            <SoftCard title="Details">
                <div className="space-y-4">
                    <InputError message={error ?? undefined} />

                    <EditableSettingRow
                        label="Name"
                        displayValue={profile.name}
                        editing={editing === 'name'}
                        saving={saving}
                        onEdit={() => startEdit('name')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('name')}
                    >
                        <Input
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            autoFocus
                        />
                    </EditableSettingRow>

                    <EditableSettingRow
                        label="Website"
                        displayValue={
                            profile.website ? (
                                <a
                                    href={profile.website}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="break-all underline-offset-4 hover:underline"
                                >
                                    {profile.website}
                                </a>
                            ) : null
                        }
                        editing={editing === 'website'}
                        saving={saving}
                        onEdit={() => startEdit('website')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('website')}
                    >
                        <Input
                            type="url"
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            autoFocus
                        />
                    </EditableSettingRow>

                    <EditableSettingRow
                        label="Country"
                        displayValue={countryLabel(profile.country)}
                        editing={editing === 'country'}
                        saving={saving}
                        onEdit={() => startEdit('country')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('country')}
                    >
                        <Select value={draft} onValueChange={setDraft}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder="Select a country" />
                            </SelectTrigger>
                            <SelectContent>
                                {countries.map((item) => (
                                    <SelectItem
                                        key={item.value}
                                        value={item.value}
                                    >
                                        {item.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </EditableSettingRow>

                    <EditableSettingRow
                        label="Value proposition"
                        displayValue={profile.value_proposition}
                        editing={editing === 'value_proposition'}
                        saving={saving}
                        onEdit={() => startEdit('value_proposition')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('value_proposition')}
                    >
                        <Textarea
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            rows={5}
                            className="min-h-28 rounded-2xl"
                            autoFocus
                        />
                    </EditableSettingRow>

                    <div className="rounded-2xl border border-border bg-card px-5 py-4">
                        <p className="mb-3 text-sm font-medium text-muted-foreground">
                            Logo
                        </p>
                        <ProfileImageField
                            id="logo"
                            label=""
                            url={
                                logo
                                    ? URL.createObjectURL(logo)
                                    : profile.logo_url
                            }
                            onFileChange={setLogo}
                            onRemove={
                                profile.logo_url && !logo
                                    ? removeLogo
                                    : undefined
                            }
                            removing={removing}
                        />
                        {logo ? (
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="accent"
                                    size="sm"
                                    disabled={saving}
                                    onClick={() => void saveLogo()}
                                >
                                    {saving ? 'Saving…' : 'Save logo'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={saving}
                                    onClick={() => setLogo(null)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        ) : null}
                    </div>

                    <EditableSettingRow
                        label="Billing email"
                        displayValue={profile.billing_email}
                        editing={editing === 'billing_email'}
                        saving={saving}
                        disabled={!profile.can_manage_money}
                        onEdit={() => startEdit('billing_email')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('billing_email')}
                    >
                        <Input
                            type="email"
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            autoFocus
                        />
                    </EditableSettingRow>
                    {!profile.can_manage_money ? (
                        <p className="text-sm text-muted-foreground">
                            Only owners can change billing details.
                        </p>
                    ) : null}
                </div>
            </SoftCard>
        </div>
    );
}
