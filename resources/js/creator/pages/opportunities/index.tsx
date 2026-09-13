import { useEffect, useState, type FormEvent } from 'react';
import { Search } from 'lucide-react';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import OpportunityCard from '@/creator/pages/opportunities/opportunity-card';
import type { Opportunity } from '@/creator/pages/opportunities/types';
import { ApiError, creatorApi, http } from '@/lib/api';

export default function CreatorOpportunitiesPage() {
    const [items, setItems] = useState<Opportunity[]>([]);
    const [query, setQuery] = useState('');
    const [applied, setApplied] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        setLoading(true);

        http.get<Opportunity[]>(creatorApi.opportunities({ q: applied || undefined }))
            .then(({ data }) => {
                setItems(data);
                setError(null);
            })
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load opportunities.',
                );
            })
            .finally(() => setLoading(false));
    }, [applied]);

    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setApplied(query.trim());
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Opportunities
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Campaigns that match your niches, country, and audience.
                </p>
            </div>
            <form onSubmit={search} className="relative max-w-xl">
                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                <Input
                    value={query}
                    onChange={(event) => setQuery(event.target.value)}
                    placeholder="Search campaigns or companies"
                    className="h-10 pl-9"
                />
            </form>
            <InputError message={error ?? undefined} />
            {loading ? (
                <p className="text-muted-foreground text-sm">Loading…</p>
            ) : items.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    No related campaigns right now.
                </p>
            ) : (
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {items.map((item) => (
                        <OpportunityCard key={item.id} opportunity={item} />
                    ))}
                </div>
            )}
        </div>
    );
}
