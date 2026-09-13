import { useEffect, useState } from 'react';
import { AppLink } from '@/components/app-link';
import InputError from '@/components/input-error';
import { ApiError, creatorApi, http } from '@/lib/api';

type Opportunity = {
    id: number;
    name: string;
    type: string;
    objective: string;
    company: { id: number; name: string | null };
};

export default function CreatorOpportunitiesPage() {
    const [items, setItems] = useState<Opportunity[]>([]);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        http.get<Opportunity[]>(creatorApi.opportunities)
            .then(({ data }) => setItems(data))
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load opportunities.',
                );
            });
    }, []);

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">
                    Opportunities
                </h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Active campaigns you can apply to.
                </p>
            </div>
            <InputError message={error ?? undefined} />
            {items.length === 0 ? (
                <p className="text-muted-foreground text-sm">
                    No open campaigns right now.
                </p>
            ) : (
                <div className="grid gap-2">
                    {items.map((item) => (
                        <AppLink
                            key={item.id}
                            href={`/opportunities/${item.id}`}
                            className="border-border hover:bg-muted/40 rounded-xl border px-4 py-3"
                        >
                            <p className="font-medium">{item.name}</p>
                            <p className="text-muted-foreground text-sm">
                                {item.company.name} · {item.type} ·{' '}
                                {item.objective}
                            </p>
                        </AppLink>
                    ))}
                </div>
            )}
        </div>
    );
}
