import { useEffect, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import { ChipToggle } from '@/components/chip-toggle';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { ProfileImageField } from '@/components/profile-image-field';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { api, ApiError, creatorApi, sharedApi } from '@/lib/api';
import { setCurrentUserAvatar } from '@/lib/current-user';
import { countries } from '@/lib/lookups';
import { cn } from '@/lib/utils';

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

export default function CreatorProfilePage() {
    const [profile, setProfile] = useState<CreatorProfile | null>(null);
    const [allNiches, setAllNiches] = useState<Niche[]>([]);
    const [selectedNicheIds, setSelectedNicheIds] = useState<number[]>([]);
    const [error, setError] = useState<string | null>(null);
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

    async function onSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        if (!profile) {
            return;
        }
        setSaving(true);
        setError(null);
        try {
            const formData = new FormData(event.currentTarget);
            formData.append('_method', 'PATCH');
            const updated = await api<CreatorProfile>(creatorApi.profile, {
                method: 'POST',
                body: formData,
            });
            await api<CreatorProfile>(creatorApi.niches, {
                method: 'PUT',
                body: JSON.stringify({ niche_ids: selectedNicheIds }),
            });
            setProfile({
                ...updated,
                niches: allNiches.filter((niche) =>
                    selectedNicheIds.includes(niche.id),
                ),
            });
            setPhoto(null);
            setCurrentUserAvatar(updated.photo_url);
            toast.success('Profile saved.');
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
            <Card>
                <CardHeader>
                    <CardTitle>Profile</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-4" onSubmit={onSubmit}>
                        <div className="space-y-2">
                            <Label htmlFor="display_name">Display name</Label>
                            <Input
                                id="display_name"
                                name="display_name"
                                defaultValue={profile.display_name ?? ''}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="headline">Headline</Label>
                            <Input
                                id="headline"
                                name="headline"
                                defaultValue={profile.headline ?? ''}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="linkedin_url">LinkedIn URL</Label>
                            <Input
                                id="linkedin_url"
                                name="linkedin_url"
                                defaultValue={profile.linkedin_url ?? ''}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Country</Label>
                            <Select
                                defaultValue={profile.country ?? undefined}
                                onValueChange={(value) => {
                                    const input = document.getElementById(
                                        'country',
                                    ) as HTMLInputElement | null;
                                    if (input) {
                                        input.value = value;
                                    }
                                }}
                            >
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
                            <input
                                id="country"
                                type="hidden"
                                name="country"
                                defaultValue={profile.country ?? ''}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="bio">Bio</Label>
                            <textarea
                                id="bio"
                                name="bio"
                                defaultValue={profile.bio ?? ''}
                                rows={4}
                                className={cn(
                                    'border-input min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none',
                                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                )}
                            />
                        </div>
                        <ProfileImageField
                            id="photo"
                            name="photo"
                            label="Photo"
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
                        <div className="space-y-2">
                            <Label>Niches</Label>
                            <div className="flex flex-wrap gap-2">
                                {allNiches.map((niche) => (
                                    <ChipToggle
                                        key={niche.id}
                                        selected={selectedNicheIds.includes(niche.id)}
                                        onToggle={() =>
                                            setSelectedNicheIds((current) =>
                                                current.includes(niche.id)
                                                    ? current.filter((id) => id !== niche.id)
                                                    : [...current, niche.id],
                                            )
                                        }
                                    >
                                        {niche.name}
                                    </ChipToggle>
                                ))}
                            </div>
                        </div>
                        <InputError message={error ?? undefined} />
                        <Button type="submit" disabled={saving}>
                            {saving ? 'Saving…' : 'Save'}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
