import { useEffect, useState } from 'react';
import { ChevronsUpDown, Search } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { api, companyApi } from '@/lib/api';
import type { CampaignList } from '@/company/pages/campaigns/types';

export type CampaignOption = {
    id: number;
    name: string;
    status: string;
};

export default function CampaignSearchSelect({
    value,
    onChange,
}: {
    value: CampaignOption | null;
    onChange: (campaign: CampaignOption | null) => void;
}) {
    const [open, setOpen] = useState(false);
    const [q, setQ] = useState('');
    const [items, setItems] = useState<CampaignOption[]>([]);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handle = window.setTimeout(() => {
            setLoading(true);
            api<CampaignList>(companyApi.campaigns({ q: q || undefined }))
                .then((data) =>
                    setItems(
                        data.data.filter(
                            (row) =>
                                row.status !== 'completed' &&
                                row.status !== 'cancelled',
                        ),
                    ),
                )
                .catch(() => setItems([]))
                .finally(() => setLoading(false));
        }, 200);

        return () => window.clearTimeout(handle);
    }, [open, q]);

    return (
        <DropdownMenu modal={false} open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    className="h-9 w-full justify-between font-normal"
                >
                    <span className="truncate">
                        {value?.name ?? 'Select a campaign'}
                    </span>
                    <ChevronsUpDown className="text-muted-foreground size-4 shrink-0" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className="w-(--radix-dropdown-menu-trigger-width) min-w-56 p-2"
                align="start"
            >
                <div className="relative mb-2">
                    <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-2 size-3.5 -translate-y-1/2" />
                    <Input
                        value={q}
                        onChange={(event) => setQ(event.target.value)}
                        onKeyDown={(event) => event.stopPropagation()}
                        placeholder="Search campaigns…"
                        className="h-8 pl-7"
                    />
                </div>
                {loading && items.length === 0 ? (
                    <p className="text-muted-foreground px-2 py-3 text-xs">
                        Searching…
                    </p>
                ) : items.length === 0 ? (
                    <p className="text-muted-foreground px-2 py-3 text-xs">
                        No campaigns match.
                    </p>
                ) : (
                    items.map((campaign) => (
                        <DropdownMenuItem
                            key={campaign.id}
                            onSelect={() => {
                                onChange(campaign);
                                setOpen(false);
                            }}
                        >
                            <span className="truncate">{campaign.name}</span>
                        </DropdownMenuItem>
                    ))
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
