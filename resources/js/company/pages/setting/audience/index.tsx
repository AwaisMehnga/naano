import { useEffect, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import { ChipToggle } from '@/components/chip-toggle';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { api, ApiError, companyApi } from '@/lib/api';
import { cn } from '@/lib/utils';

type Targeting = {
    industries: string[];
    regions: string[];
    titles: string[];
    seniority: string[];
    company_sizes: string[];
};

type Lookups = {
    industries: string[];
    regions: string[];
    seniority: string[];
    company_sizes: string[];
};

type Icp = {
    id: number;
    title: string;
    description: string;
    tags: string[];
    industries: string[];
    regions: string[];
};

function toggleValue(list: string[], value: string): string[] {
    return list.includes(value)
        ? list.filter((item) => item !== value)
        : [...list, value];
}

export default function CompanyAudiencePage() {
    const [targeting, setTargeting] = useState<Targeting | null>(null);
    const [lookups, setLookups] = useState<Lookups | null>(null);
    const [icps, setIcps] = useState<Icp[]>([]);
    const [titleInput, setTitleInput] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    async function load() {
        const [audience, icpRows] = await Promise.all([
            api<{ targeting: Targeting; lookups: Lookups }>(companyApi.audience),
            api<Icp[]>(companyApi.icps),
        ]);
        setTargeting(audience.targeting);
        setLookups(audience.lookups);
        setIcps(icpRows);
    }

    useEffect(() => {
        load().catch((caught: unknown) => {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load audience.',
            );
        });
    }, []);

    async function saveTargeting() {
        if (!targeting) {
            return;
        }
        setSaving(true);
        setError(null);
        try {
            const data = await api<{ targeting: Targeting; lookups: Lookups }>(
                companyApi.audience,
                {
                    method: 'PATCH',
                    body: JSON.stringify(targeting),
                },
            );
            setTargeting(data.targeting);
            toast.success('Audience targeting saved.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save targeting.',
            );
        } finally {
            setSaving(false);
        }
    }

    async function addIcp(event: FormEvent) {
        event.preventDefault();
        if (!targeting) {
            return;
        }
        setError(null);
        try {
            const created = await api<Icp>(companyApi.icps, {
                method: 'POST',
                body: JSON.stringify({
                    title: titleInput || 'New buyer ICP',
                    description: 'Describe this buyer and why they buy.',
                    tags: [],
                    industries: targeting.industries,
                    regions: targeting.regions,
                }),
            });
            setIcps((current) => [...current, created]);
            setTitleInput('');
            toast.success('ICP added.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not add ICP.',
            );
        }
    }

    async function saveIcp(icp: Icp) {
        setError(null);
        try {
            const updated = await api<Icp>(companyApi.icp(icp.id), {
                method: 'PATCH',
                body: JSON.stringify({
                    title: icp.title,
                    description: icp.description,
                    tags: icp.tags,
                    industries: icp.industries,
                    regions: icp.regions,
                }),
            });
            setIcps((current) =>
                current.map((row) => (row.id === updated.id ? updated : row)),
            );
            toast.success('ICP saved.');
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save ICP.',
            );
        }
    }

    async function removeIcp(id: number) {
        setError(null);
        try {
            await api(companyApi.icp(id), { method: 'DELETE' });
            setIcps((current) => current.filter((row) => row.id !== id));
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not remove ICP.',
            );
        }
    }

    if (!targeting || !lookups) {
        return (
            <p className="text-sm text-muted-foreground">{error ?? 'Loading…'}</p>
        );
    }

    return (
        <div className="space-y-6">
            <Heading
                title="Target: ICP"
                description="Who you want creators to reach."
            />
            <InputError message={error ?? undefined} />
            <Card>
                <CardHeader>
                    <CardTitle>Target industries</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-2">
                    {lookups.industries.map((item) => (
                        <ChipToggle
                            key={item}
                            selected={targeting.industries.includes(item)}
                            onToggle={() =>
                                setTargeting({
                                    ...targeting,
                                    industries: toggleValue(
                                        targeting.industries,
                                        item,
                                    ),
                                })
                            }
                        >
                            {item}
                            {targeting.industries.includes(item) ? ' ×' : ''}
                        </ChipToggle>
                    ))}
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Target regions</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-2">
                    {lookups.regions.map((item) => (
                        <ChipToggle
                            key={item}
                            selected={targeting.regions.includes(item)}
                            onToggle={() =>
                                setTargeting({
                                    ...targeting,
                                    regions: toggleValue(targeting.regions, item),
                                })
                            }
                        >
                            {item}
                        </ChipToggle>
                    ))}
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Seniority</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-2">
                    {lookups.seniority.map((item) => (
                        <ChipToggle
                            key={item}
                            selected={targeting.seniority.includes(item)}
                            onToggle={() =>
                                setTargeting({
                                    ...targeting,
                                    seniority: toggleValue(
                                        targeting.seniority,
                                        item,
                                    ),
                                })
                            }
                        >
                            {item}
                        </ChipToggle>
                    ))}
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Target company sizes</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-wrap gap-2">
                    {lookups.company_sizes.map((item) => (
                        <ChipToggle
                            key={item}
                            selected={targeting.company_sizes.includes(item)}
                            onToggle={() =>
                                setTargeting({
                                    ...targeting,
                                    company_sizes: toggleValue(
                                        targeting.company_sizes,
                                        item,
                                    ),
                                })
                            }
                        >
                            {item}
                        </ChipToggle>
                    ))}
                </CardContent>
            </Card>
            <div className="space-y-2">
                <Label htmlFor="titles">Buyer titles</Label>
                <Input
                    id="titles"
                    placeholder="VP Operations, COO, Head of Business"
                    value={targeting.titles.join(', ')}
                    onChange={(event) =>
                        setTargeting({
                            ...targeting,
                            titles: event.target.value
                                .split(',')
                                .map((item) => item.trim())
                                .filter(Boolean),
                        })
                    }
                />
            </div>
            <Button type="button" onClick={saveTargeting} disabled={saving}>
                {saving ? 'Saving…' : 'Save targeting'}
            </Button>
            <div className="flex items-center justify-between gap-4">
                <h3 className="text-lg font-medium">
                    {icps.length} buyer ICP{icps.length === 1 ? '' : 's'}
                </h3>
                <form className="flex gap-2" onSubmit={addIcp}>
                    <Input
                        value={titleInput}
                        onChange={(event) => setTitleInput(event.target.value)}
                        placeholder="Add an ICP title"
                    />
                    <Button type="submit" variant="outline">
                        Add
                    </Button>
                </form>
            </div>
            <div className="grid gap-4">
                {icps.map((icp, index) => (
                    <Card key={icp.id}>
                        <CardHeader>
                            <CardTitle>
                                {index + 1}. {icp.title}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Input
                                value={icp.title}
                                onChange={(event) =>
                                    setIcps((current) =>
                                        current.map((row) =>
                                            row.id === icp.id
                                                ? {
                                                      ...row,
                                                      title: event.target.value,
                                                  }
                                                : row,
                                        ),
                                    )
                                }
                            />
                            <textarea
                                value={icp.description}
                                onChange={(event) =>
                                    setIcps((current) =>
                                        current.map((row) =>
                                            row.id === icp.id
                                                ? {
                                                      ...row,
                                                      description:
                                                          event.target.value,
                                                  }
                                                : row,
                                        ),
                                    )
                                }
                                rows={4}
                                className={cn(
                                    'border-input min-h-24 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none',
                                    'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
                                )}
                            />
                            <p className="text-sm text-muted-foreground">
                                {(icp.tags ?? []).join(' · ') || 'No tags yet'}
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={() => saveIcp(icp)}
                                >
                                    Save
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => removeIcp(icp.id)}
                                >
                                    Remove
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </div>
    );
}
