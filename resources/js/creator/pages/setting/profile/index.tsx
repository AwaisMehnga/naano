import { useEffect, useState } from 'react';
import { Pencil } from 'lucide-react';
import { toast } from 'sonner';
import { ChipToggle } from '@/components/chip-toggle';
import { SoftCard } from '@/components/ds/soft-card';
import { IconButton } from '@/components/ds/icon-button';
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
import { api, ApiError, creatorApi, sharedApi } from '@/lib/api';
import { setCurrentUserAvatar } from '@/lib/current-user';
import { countries } from '@/lib/lookups';

type Niche = { id: number; name: string; slug: string };

type CreatorProfile = {
    display_name: string | null;
    linkedin_url: string | null;
    headline: string | null;
    photo_url: string | null;
    bio: string | null;
    country: string | null;
    vetting_status: string;
    niches: Niche[];
};

type FieldKey =
    | 'display_name'
    | 'headline'
    | 'linkedin_url'
    | 'country'
    | 'bio'
    | 'niches'
    | null;

function countryLabel(code: string | null): string | null {
    if (!code) {
        return null;
    }

    return countries.find((item) => item.value === code)?.label ?? code;
}

export default function CreatorProfilePage() {
    const [profile, setProfile] = useState<CreatorProfile | null>(null);
    const [allNiches, setAllNiches] = useState<Niche[]>([]);
    const [selectedNicheIds, setSelectedNicheIds] = useState<number[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [editing, setEditing] = useState<FieldKey>(null);
    const [draft, setDraft] = useState('');
    const [saving, setSaving] = useState(false);
    const [photo, setPhoto] = useState<File | null>(null);
    const [removing, setRemoving] = useState(false);

    useEffect(() => {
        Promise.all([
            api<CreatorProfile>(creatorApi.profile),
            api<Niche[]>(sharedApi.niches),
        ])
            .then(([data, niches]) => {
                setProfile(data);
                setAllNiches(niches);
                setSelectedNicheIds(data.niches.map((niche) => niche.id));
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

        if (field === 'niches') {
            setSelectedNicheIds(profile.niches.map((niche) => niche.id));
            return;
        }

        setDraft(
            field === 'country'
                ? (profile.country ?? '')
                : (profile[field] ?? ''),
        );
    }

    function cancelEdit() {
        setEditing(null);
        setDraft('');
        if (profile) {
            setSelectedNicheIds(profile.niches.map((niche) => niche.id));
        }
    }

    async function patchField(fields: Record<string, string>) {
        const formData = new FormData();
        formData.append('_method', 'PATCH');
        for (const [key, value] of Object.entries(fields)) {
            formData.append(key, value);
        }

        return api<CreatorProfile>(creatorApi.profile, {
            method: 'POST',
            body: formData,
        });
    }

    async function saveField(field: Exclude<FieldKey, 'niches' | null>) {
        if (!profile || !field) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            const updated = await patchField({ [field]: draft });
            setProfile({
                ...updated,
                niches: profile.niches,
            });
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

    async function saveNiches() {
        if (!profile) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            await api<CreatorProfile>(creatorApi.niches, {
                method: 'PUT',
                body: JSON.stringify({ niche_ids: selectedNicheIds }),
            });
            setProfile({
                ...profile,
                niches: allNiches.filter((niche) =>
                    selectedNicheIds.includes(niche.id),
                ),
            });
            setEditing(null);
            toast.success('Niches saved.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save niches.',
            );
        } finally {
            setSaving(false);
        }
    }

    async function savePhoto() {
        if (!photo) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('photo', photo);
            const updated = await api<CreatorProfile>(creatorApi.profile, {
                method: 'POST',
                body: formData,
            });
            setProfile((current) =>
                current
                    ? { ...updated, niches: current.niches }
                    : { ...updated, niches: [] },
            );
            setPhoto(null);
            setCurrentUserAvatar(updated.photo_url);
            toast.success('Photo saved.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save photo.',
            );
        } finally {
            setSaving(false);
        }
    }

    async function removePhoto() {
        setRemoving(true);
        setError(null);
        try {
            const formData = new FormData();
            formData.append('_method', 'PATCH');
            formData.append('remove_photo', '1');
            const updated = await api<CreatorProfile>(creatorApi.profile, {
                method: 'POST',
                body: formData,
            });
            setProfile((current) =>
                current
                    ? { ...updated, niches: current.niches }
                    : { ...updated, niches: [] },
            );
            setPhoto(null);
            setCurrentUserAvatar(null);
            toast.success('Photo removed.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not remove photo.',
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
                title="Media kit"
                description="Your public creator card. Rates live on offers, not here."
            />

            <SoftCard title="Profile">
                <div className="space-y-4">
                    <InputError message={error ?? undefined} />

                    <EditableSettingRow
                        label="Display name"
                        displayValue={profile.display_name}
                        editing={editing === 'display_name'}
                        saving={saving}
                        onEdit={() => startEdit('display_name')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('display_name')}
                    >
                        <Input
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            autoFocus
                        />
                    </EditableSettingRow>

                    <EditableSettingRow
                        label="Headline"
                        displayValue={profile.headline}
                        editing={editing === 'headline'}
                        saving={saving}
                        onEdit={() => startEdit('headline')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('headline')}
                    >
                        <Input
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            autoFocus
                        />
                    </EditableSettingRow>

                    <EditableSettingRow
                        label="LinkedIn URL"
                        displayValue={
                            profile.linkedin_url ? (
                                <a
                                    href={profile.linkedin_url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="break-all underline-offset-4 hover:underline"
                                >
                                    {profile.linkedin_url}
                                </a>
                            ) : null
                        }
                        editing={editing === 'linkedin_url'}
                        saving={saving}
                        onEdit={() => startEdit('linkedin_url')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('linkedin_url')}
                    >
                        <Input
                            type="url"
                            value={draft}
                            onChange={(event) => setDraft(event.target.value)}
                            placeholder="https://www.linkedin.com/in/you"
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
                                <SelectValue placeholder="Country" />
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
                        label="Bio"
                        displayValue={profile.bio}
                        editing={editing === 'bio'}
                        saving={saving}
                        onEdit={() => startEdit('bio')}
                        onCancel={cancelEdit}
                        onSave={() => saveField('bio')}
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
                            Photo
                        </p>
                        <ProfileImageField
                            id="photo"
                            name="photo"
                            label=""
                            url={
                                photo
                                    ? URL.createObjectURL(photo)
                                    : profile.photo_url
                            }
                            onFileChange={setPhoto}
                            onRemove={
                                profile.photo_url && !photo
                                    ? removePhoto
                                    : undefined
                            }
                            removing={removing}
                        />
                        {photo ? (
                            <div className="mt-4 flex flex-wrap gap-2">
                                <Button
                                    type="button"
                                    variant="accent"
                                    size="sm"
                                    disabled={saving}
                                    onClick={() => void savePhoto()}
                                >
                                    {saving ? 'Saving…' : 'Save photo'}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    disabled={saving}
                                    onClick={() => setPhoto(null)}
                                >
                                    Cancel
                                </Button>
                            </div>
                        ) : null}
                    </div>

                    <div className="rounded-2xl border border-border bg-card px-5 py-4">
                        <div className="flex items-start justify-between gap-3">
                            <p className="text-sm font-medium text-muted-foreground">
                                Niches
                            </p>
                            {editing !== 'niches' ? (
                                <IconButton
                                    variant="ghost"
                                    size="sm"
                                    aria-label="Edit niches"
                                    onClick={() => startEdit('niches')}
                                >
                                    <Pencil className="size-4" />
                                </IconButton>
                            ) : null}
                        </div>

                        {editing === 'niches' ? (
                            <div className="mt-3 space-y-4">
                                <div className="flex flex-wrap gap-2">
                                    {allNiches.map((niche) => (
                                        <ChipToggle
                                            key={niche.id}
                                            selected={selectedNicheIds.includes(
                                                niche.id,
                                            )}
                                            onToggle={() =>
                                                setSelectedNicheIds((current) =>
                                                    current.includes(niche.id)
                                                        ? current.filter(
                                                              (id) =>
                                                                  id !==
                                                                  niche.id,
                                                          )
                                                        : [
                                                              ...current,
                                                              niche.id,
                                                          ],
                                                )
                                            }
                                        >
                                            {niche.name}
                                        </ChipToggle>
                                    ))}
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="accent"
                                        size="sm"
                                        disabled={saving}
                                        onClick={() => void saveNiches()}
                                    >
                                        {saving ? 'Saving…' : 'Save'}
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={saving}
                                        onClick={cancelEdit}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        ) : (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {profile.niches.length === 0 ? (
                                    <span className="text-sm text-muted-foreground">
                                        Not set
                                    </span>
                                ) : (
                                    profile.niches.map((niche) => (
                                        <span
                                            key={niche.id}
                                            className="rounded-pill bg-lime-soft px-3 py-1 text-xs font-medium text-lime-soft-foreground"
                                        >
                                            {niche.name}
                                        </span>
                                    ))
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </SoftCard>
        </div>
    );
}
