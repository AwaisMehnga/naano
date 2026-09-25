import { useEffect, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
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
import { api, ApiError, companyApi } from '@/lib/api';
import { setCurrentUserAvatar } from '@/lib/current-user';
import { countries } from '@/lib/lookups';
import { cn } from '@/lib/utils';

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

export default function CompanyProfilePage() {
    const [profile, setProfile] = useState<CompanyProfile | null>(null);
    const [name, setName] = useState('');
    const [website, setWebsite] = useState('');
    const [country, setCountry] = useState('');
    const [valueProposition, setValueProposition] = useState('');
    const [billingEmail, setBillingEmail] = useState('');
    const [logo, setLogo] = useState<File | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);
    const [removing, setRemoving] = useState(false);

    useEffect(() => {
        api<CompanyProfile>(companyApi.profile)
            .then((data) => {
                setProfile(data);
                setName(data.name ?? '');
                setWebsite(data.website ?? '');
                setCountry(data.country ?? '');
                setValueProposition(data.value_proposition ?? '');
                setBillingEmail(data.billing_email ?? '');
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

        const formData = new FormData();
        formData.append('name', name);
        formData.append('website', website);
        formData.append('country', country);
        formData.append('value_proposition', valueProposition);
        if (profile.can_manage_money) {
            formData.append('billing_email', billingEmail);
        }
        if (logo) {
            formData.append('logo', logo);
        }

        setSaving(true);
        setError(null);

        try {
            const updated = await api<CompanyProfile>(companyApi.profile, {
                method: 'POST',
                body: (() => {
                    formData.append('_method', 'PATCH');
                    return formData;
                })(),
            });
            setProfile(updated);
            setLogo(null);
            setCurrentUserAvatar(updated.logo_url);
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
            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form className="space-y-4" onSubmit={onSubmit}>
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={name}
                                onChange={(event) => setName(event.target.value)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="website">Website</Label>
                            <Input
                                id="website"
                                type="url"
                                value={website}
                                onChange={(event) =>
                                    setWebsite(event.target.value)
                                }
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="country">Country</Label>
                            <Select value={country} onValueChange={setCountry}>
                                <SelectTrigger id="country" className="w-full">
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
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="value_proposition">
                                Value proposition
                            </Label>
                            <textarea
                                id="value_proposition"
                                value={valueProposition}
                                onChange={(event) =>
                                    setValueProposition(event.target.value)
                                }
                                rows={5}
                                className={cn(
                                    'border-input min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none',
                                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                )}
                            />
                        </div>
                        <ProfileImageField
                            id="logo"
                            label="Logo"
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
                        <div className="space-y-2">
                            <Label htmlFor="billing_email">Billing email</Label>
                            <Input
                                id="billing_email"
                                type="email"
                                value={billingEmail}
                                disabled={!profile.can_manage_money}
                                onChange={(event) =>
                                    setBillingEmail(event.target.value)
                                }
                            />
                            {!profile.can_manage_money && (
                                <p className="text-sm text-muted-foreground">
                                    Only owners can change billing details.
                                </p>
                            )}
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
