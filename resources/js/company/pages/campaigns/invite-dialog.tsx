import { useEffect, useState } from 'react';
import { Search } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type { CreatorList } from '@/company/pages/creators/types';
import { ApiError, http, companyApi } from '@/lib/api';
import { useCampaigns } from './store';

export default function InviteCreatorDialog({
    campaignId,
    open,
    onOpenChange,
}: {
    campaignId: number;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const invite = useCampaigns((state) => state.invite);
    const [q, setQ] = useState('');
    const [items, setItems] = useState<CreatorList['items']>([]);
    const [loading, setLoading] = useState(false);
    const [inviting, setInviting] = useState<number | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handle = window.setTimeout(() => {
            setLoading(true);
            http.get<CreatorList>(companyApi.creators({ q: q || undefined }))
                .then(({ data }) => setItems(data.items))
                .catch(() => setItems([]))
                .finally(() => setLoading(false));
        }, 200);

        return () => window.clearTimeout(handle);
    }, [open, q]);

    async function send(creatorId: number) {
        setInviting(creatorId);

        try {
            await invite(campaignId, creatorId);
            toast.success('Invitation sent');
            onOpenChange(false);
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not invite this creator.',
            );
        } finally {
            setInviting(null);
        }
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Invite a creator</DialogTitle>
                    <DialogDescription>
                        Search vetted creators and send an invitation.
                    </DialogDescription>
                </DialogHeader>
                <div className="relative">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-2 size-3.5 -translate-y-1/2" />
                    <Input
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                        placeholder="Search creators…"
                        className="pl-7"
                    />
                </div>
                <div className="grid max-h-72 gap-2 overflow-y-auto">
                    {loading && items.length === 0 ? (
                        <p className="text-muted-foreground px-1 py-3 text-sm">
                            Searching…
                        </p>
                    ) : items.length === 0 ? (
                        <p className="text-muted-foreground px-1 py-3 text-sm">
                            No creators match.
                        </p>
                    ) : (
                        items.map((creator) => (
                            <div
                                key={creator.id}
                                className="border-border flex items-center justify-between gap-3 rounded-xl border px-3 py-2"
                            >
                                <div className="min-w-0">
                                    <p className="truncate text-sm font-medium">
                                        {creator.display_name}
                                    </p>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {creator.headline}
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    size="sm"
                                    disabled={inviting === creator.id}
                                    onClick={() => void send(creator.id)}
                                >
                                    Invite
                                </Button>
                            </div>
                        ))
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
