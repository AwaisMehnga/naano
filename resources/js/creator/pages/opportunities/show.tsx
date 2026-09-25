import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import InputError from '@/components/input-error';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { initials } from '@/company/pages/creators/format';
import {
    deadlineLabel,
    locationLabel,
    scoreLabel,
} from '@/creator/pages/opportunities/format';
import type { OpportunityDetail } from '@/creator/pages/opportunities/types';
import { ApiError, creatorApi, http } from '@/lib/api';

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
                    <div className="flex flex-wrap items-start gap-4">
                        <Avatar className="size-14 rounded-lg">
                            {item.company.logo_url && (
                                <AvatarImage
                                    src={item.company.logo_url}
                                    alt=""
                                />
                            )}
                            <AvatarFallback className="rounded-lg">
                                {initials(item.company.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="min-w-0 flex-1">
                            <p className="text-muted-foreground text-sm">
                                {item.company.name}
                            </p>
                            <h1 className="text-2xl font-semibold tracking-tight">
                                {item.name}
                            </h1>
                            <p className="text-muted-foreground mt-2 text-sm">
                                {locationLabel(item.location)} · Deadline{' '}
                                {deadlineLabel(item.deadline)}
                            </p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Badge variant="secondary">
                                Match {scoreLabel(item.match_score)}
                            </Badge>
                            <Badge variant="outline">
                                Audience {scoreLabel(item.audience_relevance)}
                            </Badge>
                        </div>
                    </div>
                    <section className="space-y-2">
                        <h2 className="text-sm font-medium">Brief</h2>
                        <p className="text-sm leading-7 whitespace-pre-wrap">
                            {item.brief?.context ?? item.goal ?? 'No brief yet.'}
                        </p>
                    </section>
                    {item.key_messages && item.key_messages.length > 0 && (
                        <section className="space-y-2">
                            <h2 className="text-sm font-medium">
                                Key messages
                            </h2>
                            <ul className="list-disc space-y-1 pl-5 text-sm">
                                {item.key_messages.map((message) => (
                                    <li key={message}>{message}</li>
                                ))}
                            </ul>
                        </section>
                    )}
                    {item.guidelines && (
                        <section className="space-y-2">
                            <h2 className="text-sm font-medium">Guidelines</h2>
                            <p className="text-muted-foreground text-sm whitespace-pre-wrap">
                                {item.guidelines}
                            </p>
                        </section>
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
