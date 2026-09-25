import { useEffect, useState } from 'react';
import { Copy } from 'lucide-react';
import { toast } from 'sonner';
import { SoftCard } from '@/components/ds';
import { IconButton } from '@/components/ds/icon-button';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError, companyApi, http } from '@/lib/api';
import { useCampaigns } from './store';

type TrackingLink = {
    id: number;
    collaboration_id: number;
    destination_url: string;
    slug: string;
    short_url: string;
};

function pixelSnippet(slug: string): string {
    return `<script src="${window.location.origin}/pixel.js?s=${slug}" data-slug="${slug}"></script>`;
}

export default function CampaignTracking({
    campaignId,
}: {
    campaignId: number;
}) {
    const collaborations = useCampaigns((state) => state.collaborations);
    const fetchCollaborations = useCampaigns(
        (state) => state.fetchCollaborations,
    );
    const [links, setLinks] = useState<TrackingLink[]>([]);
    const [targets, setTargets] = useState<Record<number, string>>({});
    const [collaborationId, setCollaborationId] = useState('');
    const [targetUrl, setTargetUrl] = useState('');
    const [busy, setBusy] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function load() {
        const { data } = await http.get<TrackingLink[]>(
            companyApi.campaignTrackingLinks(campaignId),
        );
        setLinks(data);
        setTargets(
            Object.fromEntries(
                data.map((link) => [link.id, link.destination_url]),
            ),
        );
        setError(null);
    }

    useEffect(() => {
        void fetchCollaborations(campaignId, 'all');
        load().catch((caught: unknown) => {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load tracking links.',
            );
        });
    }, [campaignId, fetchCollaborations]);

    async function copy(value: string, label = 'Copied') {
        await navigator.clipboard.writeText(value);
        toast.success(label);
    }

    async function saveTarget(link: TrackingLink) {
        const next = (targets[link.id] ?? '').trim();

        if (next === '') {
            toast.error('Enter a target URL.');

            return;
        }

        setBusy(true);

        try {
            await http.patch(companyApi.trackingLink(link.id), {
                destination_url: next,
            });
            toast.success('Target URL saved');
            await load();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not update this link.',
            );
        } finally {
            setBusy(false);
        }
    }

    async function addTargetedUrl() {
        setBusy(true);

        try {
            await http.post(companyApi.campaignTrackingLinks(campaignId), {
                collaboration_id: Number(collaborationId),
                destination_url: targetUrl.trim(),
            });
            setTargetUrl('');
            toast.success('Tracking link created');
            await load();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not create this link.',
            );
        } finally {
            setBusy(false);
        }
    }

    const booked = collaborations.filter((row) => row.status === 'booked');

    function creatorLabel(collaborationIdValue: number): string {
        const row = collaborations.find(
            (item) => item.id === collaborationIdValue,
        );

        return row?.creator.display_name ?? `Deal #${collaborationIdValue}`;
    }

    return (
        <div className="grid gap-5">
            <SoftCard title="Tracking">
                <p className="mb-5 text-sm text-muted-foreground">
                    Creators paste the hire link as the LinkedIn CTA. Set where
                    each link should send visitors, and add more targets when
                    you need them.
                </p>

                {error ? (
                    <p className="text-sm text-destructive">{error}</p>
                ) : links.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Book a creator to generate a tracking link.
                    </p>
                ) : (
                    <div className="grid gap-5">
                        {links.map((link) => (
                            <div
                                key={link.id}
                                className="grid gap-4 rounded-3xl bg-muted p-5"
                            >
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <p className="text-sm font-medium">
                                        {creatorLabel(link.collaboration_id)}
                                    </p>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`short-${link.id}`}>
                                        Hire link
                                    </Label>
                                    <div className="flex items-center gap-2">
                                        <Input
                                            id={`short-${link.id}`}
                                            readOnly
                                            value={link.short_url}
                                            className="font-mono text-sm"
                                        />
                                        <IconButton
                                            type="button"
                                            variant="default"
                                            aria-label="Copy hire link"
                                            onClick={() =>
                                                void copy(link.short_url)
                                            }
                                        >
                                            <Copy />
                                        </IconButton>
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`target-${link.id}`}>
                                        Target URL
                                    </Label>
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Input
                                            id={`target-${link.id}`}
                                            type="url"
                                            value={targets[link.id] ?? ''}
                                            onChange={(event) =>
                                                setTargets((current) => ({
                                                    ...current,
                                                    [link.id]:
                                                        event.target.value,
                                                }))
                                            }
                                            placeholder="https://example.com/offer"
                                            className="min-w-0 flex-1"
                                        />
                                        <Button
                                            type="button"
                                            variant="outline"
                                            className="rounded-pill"
                                            disabled={busy}
                                            onClick={() =>
                                                void saveTarget(link)
                                            }
                                        >
                                            Save
                                        </Button>
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor={`pixel-${link.id}`}>
                                        Website pixel
                                    </Label>
                                    <div className="flex items-center gap-2">
                                        <Input
                                            id={`pixel-${link.id}`}
                                            readOnly
                                            value={pixelSnippet(link.slug)}
                                            className="font-mono text-xs"
                                        />
                                        <IconButton
                                            type="button"
                                            variant="default"
                                            aria-label="Copy website pixel"
                                            onClick={() =>
                                                void copy(
                                                    pixelSnippet(link.slug),
                                                    'Pixel copied',
                                                )
                                            }
                                        >
                                            <Copy />
                                        </IconButton>
                                    </div>
                                    <p className="text-xs text-muted-foreground">
                                        Paste on the target site. After 30
                                        seconds Naano records a qualified
                                        visit.
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </SoftCard>

            {booked.length > 0 ? (
                <SoftCard title="Add targeted URL">
                    <form
                        className="grid gap-5"
                        onSubmit={(event) => {
                            event.preventDefault();
                            void addTargetedUrl();
                        }}
                    >
                        <p className="text-sm text-muted-foreground">
                            Create another hire link for a booked creator with
                            a different target page.
                        </p>
                        <div className="grid gap-2">
                            <Label htmlFor="tracking_collaboration">
                                Collaboration
                            </Label>
                            <select
                                id="tracking_collaboration"
                                className="h-11 rounded-pill border border-input bg-card px-4 text-sm"
                                value={collaborationId}
                                onChange={(event) =>
                                    setCollaborationId(event.target.value)
                                }
                            >
                                <option value="">Select a booked creator</option>
                                {booked.map((row) => (
                                    <option key={row.id} value={row.id}>
                                        {row.creator.display_name ??
                                            `Deal ${row.id}`}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="tracking_target">Target URL</Label>
                            <Input
                                id="tracking_target"
                                type="url"
                                value={targetUrl}
                                onChange={(event) =>
                                    setTargetUrl(event.target.value)
                                }
                                placeholder="https://example.com/landing"
                                className="rounded-pill"
                            />
                        </div>
                        <Button
                            type="submit"
                            variant="accent"
                            className="w-fit rounded-pill"
                            disabled={
                                busy ||
                                collaborationId === '' ||
                                targetUrl.trim() === ''
                            }
                        >
                            {busy ? 'Adding…' : 'Add targeted URL'}
                        </Button>
                    </form>
                </SoftCard>
            ) : null}
        </div>
    );
}
