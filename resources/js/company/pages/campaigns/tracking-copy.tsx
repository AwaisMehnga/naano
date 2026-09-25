import { useEffect, useState } from 'react';
import { Copy } from 'lucide-react';
import { toast } from 'sonner';
import { SoftCard } from '@/components/ds';
import { IconButton } from '@/components/ds/icon-button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ApiError, companyApi, http } from '@/lib/api';

type TrackingLink = {
    id: number;
    slug: string;
    short_url: string;
};

export default function CampaignTrackingCopy({
    campaignId,
}: {
    campaignId: number;
}) {
    const [link, setLink] = useState<TrackingLink | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        let cancelled = false;

        http.get<TrackingLink[]>(companyApi.campaignTrackingLinks(campaignId))
            .then(({ data }) => {
                if (!cancelled) {
                    setLink(data[0] ?? null);
                    setError(null);
                }
            })
            .catch((caught: unknown) => {
                if (!cancelled) {
                    setError(
                        caught instanceof ApiError
                            ? caught.message
                            : 'Could not load tracking links.',
                    );
                }
            });

        return () => {
            cancelled = true;
        };
    }, [campaignId]);

    async function copy(value: string) {
        await navigator.clipboard.writeText(value);
        toast.success('Copied');
    }

    const pixel =
        link === null
            ? null
            : `<script src="${window.location.origin}/pixel.js?s=${link.slug}" data-slug="${link.slug}"></script>`;

    return (
        <SoftCard title="Tracking">
            {error ? (
                <p className="text-sm text-destructive">{error}</p>
            ) : link === null ? (
                <p className="text-sm text-muted-foreground">
                    Book a creator to get a tracking link and website pixel.
                </p>
            ) : (
                <div className="grid gap-5">
                    <div className="grid gap-2">
                        <Label htmlFor="tracking-url">Tracking URL</Label>
                        <div className="flex items-center gap-2">
                            <Input
                                id="tracking-url"
                                readOnly
                                value={link.short_url}
                                className="font-mono text-sm"
                            />
                            <IconButton
                                type="button"
                                variant="default"
                                aria-label="Copy tracking URL"
                                onClick={() => void copy(link.short_url)}
                            >
                                <Copy />
                            </IconButton>
                        </div>
                    </div>
                    {pixel ? (
                        <div className="grid gap-2">
                            <Label htmlFor="website-pixel">Website pixel</Label>
                            <div className="flex items-center gap-2">
                                <Input
                                    id="website-pixel"
                                    readOnly
                                    value={pixel}
                                    className="font-mono text-xs"
                                />
                                <IconButton
                                    type="button"
                                    variant="default"
                                    aria-label="Copy website pixel"
                                    onClick={() => void copy(pixel)}
                                >
                                    <Copy />
                                </IconButton>
                            </div>
                            <p className="text-sm text-muted-foreground">
                                Paste on the destination site. After 30 seconds
                                Naano records a qualified visit.
                            </p>
                        </div>
                    ) : null}
                </div>
            )}
        </SoftCard>
    );
}
