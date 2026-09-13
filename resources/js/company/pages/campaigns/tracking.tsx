import { useEffect, useState } from 'react';
import { toast } from 'sonner';
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
    utm_source: string | null;
    utm_medium: string | null;
    utm_campaign: string | null;
    utm_content: string | null;
};

export default function CampaignTracking({
    campaignId,
}: {
    campaignId: number;
}) {
    const collaborations = useCampaigns((state) => state.collaborations);
    const fetchCollaborations = useCampaigns((state) => state.fetchCollaborations);
    const [links, setLinks] = useState<TrackingLink[]>([]);
    const [destinations, setDestinations] = useState<Record<number, string>>({});
    const [collaborationId, setCollaborationId] = useState('');
    const [destination, setDestination] = useState('');
    const [busy, setBusy] = useState(false);

    async function load() {
        const { data } = await http.get<TrackingLink[]>(
            companyApi.campaignTrackingLinks(campaignId),
        );
        setLinks(data);
        setDestinations(
            Object.fromEntries(
                data.map((link) => [link.id, link.destination_url]),
            ),
        );
    }

    useEffect(() => {
        void fetchCollaborations(campaignId, 'all');
        load().catch((caught: unknown) => {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load tracking links.',
            );
        });
    }, [campaignId, fetchCollaborations]);

    async function copy(url: string) {
        await navigator.clipboard.writeText(url);
        toast.success('Copied');
    }

    async function save(link: TrackingLink) {
        setBusy(true);

        try {
            await http.patch(companyApi.trackingLink(link.id), {
                destination_url: destinations[link.id],
            });
            toast.success('Destination saved');
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

    async function addExtra() {
        setBusy(true);

        try {
            await http.post(companyApi.campaignTrackingLinks(campaignId), {
                collaboration_id: Number(collaborationId),
                destination_url: destination,
            });
            setDestination('');
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

    return (
        <section className="grid gap-6">
            <div>
                <h2 className="text-lg font-semibold">Tracking</h2>
                <p className="text-muted-foreground text-sm">
                    Creators paste the hire link as the LinkedIn CTA. Visitors
                    hop through Naano, then your site.
                </p>
            </div>
            {links.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    Book a creator to generate a unique UTM link.
                </p>
            ) : (
                <div className="grid gap-3">
                    {links.map((link) => (
                        <article
                            key={link.id}
                            className="border-border bg-card grid gap-3 rounded-2xl border p-5"
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <p className="font-mono text-sm break-all">
                                    {link.short_url}
                                </p>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    onClick={() => void copy(link.short_url)}
                                >
                                    Copy
                                </Button>
                            </div>
                            <p className="text-muted-foreground text-xs">
                                utm_source={link.utm_source} · utm_medium=
                                {link.utm_medium} · utm_campaign=
                                {link.utm_campaign} · utm_content=
                                {link.utm_content}
                            </p>
                            <div className="grid gap-2">
                                <Label htmlFor={`destination-${link.id}`}>
                                    Destination
                                </Label>
                                <div className="flex flex-wrap gap-2">
                                    <Input
                                        id={`destination-${link.id}`}
                                        value={destinations[link.id] ?? ''}
                                        onChange={(event) =>
                                            setDestinations((current) => ({
                                                ...current,
                                                [link.id]: event.target.value,
                                            }))
                                        }
                                    />
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={busy}
                                        onClick={() => void save(link)}
                                    >
                                        Save
                                    </Button>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            )}
            {links[0] && (
                <div className="border-border bg-card grid gap-3 rounded-2xl border p-5">
                    <h3 className="font-medium">Website pixel</h3>
                    <p className="text-muted-foreground text-sm">
                        Add this to the destination site. After 30 seconds it
                        records a qualified visit; form submits become leads.
                    </p>
                    <pre className="bg-muted overflow-x-auto rounded-md p-3 text-xs">
                        {`<script src="${window.location.origin}/pixel.js?s=${links[0].slug}" data-slug="${links[0].slug}"></script>`}
                    </pre>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        className="w-fit"
                        onClick={() =>
                            void copy(
                                `<script src="${window.location.origin}/pixel.js?s=${links[0].slug}" data-slug="${links[0].slug}"></script>`,
                            )
                        }
                    >
                        Copy snippet
                    </Button>
                </div>
            )}
            {booked.length > 0 && (
                <form
                    className="border-border grid gap-3 rounded-2xl border p-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void addExtra();
                    }}
                >
                    <h3 className="font-medium">Extra link</h3>
                    <div className="grid gap-2">
                        <Label htmlFor="tracking_collaboration">
                            Collaboration
                        </Label>
                        <select
                            id="tracking_collaboration"
                            className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                            value={collaborationId}
                            onChange={(event) =>
                                setCollaborationId(event.target.value)
                            }
                        >
                            <option value="">Select a booked creator</option>
                            {booked.map((row) => (
                                <option key={row.id} value={row.id}>
                                    {row.creator.display_name ?? `Deal ${row.id}`}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="tracking_destination">
                            Destination URL
                        </Label>
                        <Input
                            id="tracking_destination"
                            value={destination}
                            onChange={(event) =>
                                setDestination(event.target.value)
                            }
                            placeholder="https://example.com/offer"
                        />
                    </div>
                    <Button
                        type="submit"
                        disabled={
                            busy ||
                            collaborationId === '' ||
                            destination.trim() === ''
                        }
                    >
                        Add link
                    </Button>
                </form>
            )}
        </section>
    );
}
