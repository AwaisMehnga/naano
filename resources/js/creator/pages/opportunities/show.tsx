import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { ApiError, creatorApi, http } from '@/lib/api';

type OpportunityDetail = {
    id: number;
    name: string;
    type: string;
    objective: string;
    brief: { context?: string } | null;
    goal: string | null;
    guidelines: string | null;
    company: { name: string | null };
};

export default function CreatorOpportunityShowPage() {
    const { id } = useParams();
    const campaignId = Number(id);
    const navigate = useNavigate();
    const [item, setItem] = useState<OpportunityDetail | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!Number.isFinite(campaignId) || campaignId < 1) {
            return;
        }

        http.get<OpportunityDetail>(creatorApi.opportunity(campaignId))
            .then(({ data }) => setItem(data))
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load this opportunity.',
                );
            });
    }, [campaignId]);

    async function apply() {
        setSaving(true);

        try {
            await http.post(creatorApi.opportunityApply(campaignId));
            toast.success('Application sent');
            void navigate('/deals');
        } catch (caught) {
            setSaving(false);
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not apply.',
            );
        }
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-6">
            <Button
                type="button"
                variant="ghost"
                className="w-fit px-0"
                onClick={() => void navigate('/opportunities')}
            >
                <ArrowLeft className="size-4" />
                Opportunities
            </Button>
            <InputError message={error ?? undefined} />
            {item && (
                <>
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {item.name}
                        </h1>
                        <p className="text-muted-foreground mt-2 text-sm">
                            {item.company.name} · {item.type} · {item.objective}
                        </p>
                    </div>
                    <p className="text-sm leading-7 whitespace-pre-wrap">
                        {item.brief?.context ?? item.goal ?? 'No brief yet.'}
                    </p>
                    {item.guidelines && (
                        <p className="text-muted-foreground text-sm whitespace-pre-wrap">
                            {item.guidelines}
                        </p>
                    )}
                    <Button
                        type="button"
                        className="w-fit"
                        onClick={() => void apply()}
                        disabled={saving}
                    >
                        Apply
                    </Button>
                </>
            )}
        </div>
    );
}
