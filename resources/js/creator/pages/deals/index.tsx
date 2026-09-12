import { useEffect, useState } from 'react';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { euros } from '@/company/pages/creators/format';
import { ApiError, creatorApi, http } from '@/lib/api';

type Deal = {
    id: number;
    status: string;
    source: string;
    booked_price_cents: number | null;
    campaign: { id: number; name: string };
    company: { name: string | null };
};

export default function CreatorDealsPage() {
    const [items, setItems] = useState<Deal[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState<number | null>(null);

    async function load() {
        const { data } = await http.get<Deal[]>(creatorApi.collaborations());
        setItems(data);
    }

    useEffect(() => {
        load().catch((caught: unknown) => {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load deals.',
            );
        });
    }, []);

    async function act(id: number, action: 'accept' | 'decline') {
        setBusy(id);

        try {
            await http.post(
                action === 'accept'
                    ? creatorApi.collaborationAccept(id)
                    : creatorApi.collaborationDecline(id),
            );
            await load();
        } catch (caught) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not update this deal.',
            );
        } finally {
            setBusy(null);
        }
    }

    return (
        <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4 lg:p-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">Deals</h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Invites, applications, and booked collaborations.
                </p>
            </div>
            <InputError message={error ?? undefined} />
            {items.length === 0 ? (
                <p className="text-muted-foreground text-sm">No deals yet.</p>
            ) : (
                <div className="grid gap-2">
                    {items.map((item) => (
                        <div
                            key={item.id}
                            className="border-border flex flex-wrap items-center justify-between gap-3 rounded-xl border px-4 py-3"
                        >
                            <div>
                                <p className="font-medium">
                                    {item.campaign.name}
                                </p>
                                <p className="text-muted-foreground text-sm">
                                    {item.company.name} · {item.source} ·{' '}
                                    {item.status}
                                    {item.booked_price_cents
                                        ? ` · ${euros(item.booked_price_cents)}`
                                        : ''}
                                </p>
                            </div>
                            <div className="flex gap-2">
                                {item.status === 'invited' && (
                                    <>
                                        <Button
                                            type="button"
                                            size="sm"
                                            disabled={busy === item.id}
                                            onClick={() =>
                                                void act(item.id, 'accept')
                                            }
                                        >
                                            Accept
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant="ghost"
                                            disabled={busy === item.id}
                                            onClick={() =>
                                                void act(item.id, 'decline')
                                            }
                                        >
                                            Decline
                                        </Button>
                                    </>
                                )}
                                {item.status === 'applied' && (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="ghost"
                                        disabled={busy === item.id}
                                        onClick={() =>
                                            void act(item.id, 'decline')
                                        }
                                    >
                                        Withdraw
                                    </Button>
                                )}
                                {item.status === 'booked' && (
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        asChild
                                    >
                                        <AppLink
                                            href={`/collaborations/${item.id}/contract`}
                                        >
                                            Contract
                                        </AppLink>
                                    </Button>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
