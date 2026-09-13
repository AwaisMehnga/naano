import { useEffect, useState, type FormEvent } from 'react';
import { Search } from 'lucide-react';
import CollaborationChatSheet from '@/components/collaboration-chat-sheet';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import DealCard from '@/creator/pages/deals/deal-card';
import type { Deal } from '@/creator/pages/deals/types';
import { ApiError, creatorApi, http } from '@/lib/api';

const statuses = [
    'invited',
    'applied',
    'selected',
    'booked',
    'completed',
    'declined',
    'cancelled',
] as const;

const sources = ['invite', 'apply', 'sourced'] as const;

export default function CreatorDealsPage() {
    const [items, setItems] = useState<Deal[]>([]);
    const [query, setQuery] = useState('');
    const [applied, setApplied] = useState('');
    const [status, setStatus] = useState('all');
    const [source, setSource] = useState('all');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);
    const [threadId, setThreadId] = useState<number | null>(null);
    const threadRow = items.find((item) => item.id === threadId);

    useEffect(() => {
        setLoading(true);

        http.get<Deal[]>(
            creatorApi.collaborations({
                q: applied || undefined,
                status: status === 'all' ? undefined : status,
                source: source === 'all' ? undefined : source,
            }),
        )
            .then(({ data }) => {
                setItems(data);
                setError(null);
            })
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load deals.',
                );
            })
            .finally(() => setLoading(false));
    }, [applied, status, source]);

    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setApplied(query.trim());
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">Deals</h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Invites, applications, and booked collaborations.
                </p>
            </div>
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                <form onSubmit={search} className="relative min-w-0 flex-1">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                    <Input
                        value={query}
                        onChange={(event) => setQuery(event.target.value)}
                        placeholder="Search campaigns or companies"
                        className="h-10 pl-9"
                    />
                </form>
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger className="h-10 w-full sm:w-40" size="default">
                        <SelectValue placeholder="Status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All statuses</SelectItem>
                        {statuses.map((value) => (
                            <SelectItem key={value} value={value}>
                                {label(value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <Select value={source} onValueChange={setSource}>
                    <SelectTrigger className="h-10 w-full sm:w-40" size="default">
                        <SelectValue placeholder="Source" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All sources</SelectItem>
                        {sources.map((value) => (
                            <SelectItem key={value} value={value}>
                                {label(value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            <InputError message={error ?? undefined} />
            {loading ? (
                <p className="text-muted-foreground text-sm">Loading…</p>
            ) : items.length === 0 ? (
                <p className="text-muted-foreground text-sm">No deals yet.</p>
            ) : (
                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    {items.map((item) => (
                        <DealCard
                            key={item.id}
                            deal={item}
                            onMessage={(deal) => setThreadId(deal.id)}
                        />
                    ))}
                </div>
            )}
            <CollaborationChatSheet
                open={threadId !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setThreadId(null);
                    }
                }}
                collaborationId={threadId}
                title={threadRow?.company.name ?? 'Messages'}
                side="creator"
                canSend={threadRow?.status !== 'cancelled'}
            />
        </div>
    );
}

function label(value: string): string {
    return value.replaceAll('_', ' ').replace(/^\w/, (letter) => letter.toUpperCase());
}
