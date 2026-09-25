import { memo, useEffect, useState } from 'react';
import { toast } from 'sonner';
import { CalendarDays, FileText, Wallet } from 'lucide-react';
import { CampaignOpportunityCard } from '@/components/campaign-opportunity-card';
import { InfoChip } from '@/components/info-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { euros, initials } from '@/company/pages/creators/format';
import {
    deadlineLabel,
    locationLabel,
    scoreLabel,
} from '@/creator/pages/opportunities/format';
import type {
    Opportunity,
    OpportunityDetail,
} from '@/creator/pages/opportunities/types';
import { ApiError, creatorApi, http } from '@/lib/api';

function OpportunityCard({
    opportunity,
    onApplied,
}: {
    opportunity: Opportunity;
    onApplied?: () => void;
}) {
    const [open, setOpen] = useState(false);
    const [detail, setDetail] = useState<OpportunityDetail | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (!open) {
            return;
        }

        let cancelled = false;
        setLoading(true);
        setError(null);

        http.get<OpportunityDetail>(creatorApi.opportunity(opportunity.id))
            .then(({ data }) => {
                if (!cancelled) {
                    setDetail(data);
                }
            })
            .catch((caught: unknown) => {
                if (!cancelled) {
                    setError(
                        caught instanceof ApiError
                            ? caught.message
                            : 'Could not load this opportunity.',
                    );
                }
            })
            .finally(() => {
                if (!cancelled) {
                    setLoading(false);
                }
            });

        return () => {
            cancelled = true;
        };
    }, [open, opportunity.id]);

    async function apply() {
        setSaving(true);

        try {
            await http.post(creatorApi.opportunityApply(opportunity.id));
            toast.success('Application sent');
            setOpen(false);
            setDetail(null);
            onApplied?.();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not apply.',
            );
        } finally {
            setSaving(false);
        }
    }

    const budgetValue =
        opportunity.budget_cents == null
            ? 'On application'
            : euros(opportunity.budget_cents);

    const deadlineValue =
        opportunity.deadline == null
            ? 'Open ended'
            : deadlineLabel(opportunity.deadline);

    return (
        <>
            <CampaignOpportunityCard
                badgeLabel={`${scoreLabel(opportunity.match_score)} match`}
                companyName={opportunity.company.name ?? 'Company'}
                companyLogoUrl={opportunity.company.logo_url}
                companyWebsite={opportunity.company.website}
                title={opportunity.name}
                tagline={opportunity.tagline}
                stats={[
                    {
                        icon: <Wallet className="size-4" />,
                        value: budgetValue,
                        label: 'Budget',
                    },
                    {
                        icon: <FileText className="size-4" />,
                        value: opportunity.deliverables,
                        label: 'Deliverables',
                    },
                    {
                        icon: <CalendarDays className="size-4" />,
                        value: deadlineValue,
                        label: 'Deadline',
                    },
                ]}
                secondaryLabel="Read brief"
                primaryLabel="Apply"
                onSecondary={() => setOpen(true)}
                onPrimary={() => setOpen(true)}
            />

            {open ? (
                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="max-h-[85vh] overflow-y-auto rounded-3xl border-border sm:max-w-lg">
                        <DialogHeader>
                            <div className="flex items-start gap-3 pr-6">
                                <Avatar size="md" className="rounded-xl">
                                    {opportunity.company.logo_url ? (
                                        <AvatarImage
                                            src={opportunity.company.logo_url}
                                            alt=""
                                            className="object-cover"
                                        />
                                    ) : null}
                                    <AvatarFallback className="rounded-xl">
                                        {initials(opportunity.company.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="min-w-0 space-y-1 text-left">
                                    <DialogDescription className="text-sm">
                                        {opportunity.company.name}
                                    </DialogDescription>
                                    <DialogTitle className="text-xl">
                                        {opportunity.name}
                                    </DialogTitle>
                                </div>
                            </div>
                        </DialogHeader>

                        <InputError message={error ?? undefined} />

                        {loading ? (
                            <p className="text-sm text-muted-foreground">
                                Loading brief…
                            </p>
                        ) : detail ? (
                            <div className="space-y-5">
                                <div className="flex flex-wrap gap-1.5">
                                    <InfoChip>
                                        Match {scoreLabel(detail.match_score)}
                                    </InfoChip>
                                    <InfoChip>{budgetValue}</InfoChip>
                                    <InfoChip>{detail.deliverables}</InfoChip>
                                    <InfoChip>
                                        Due {deadlineLabel(detail.deadline)}
                                    </InfoChip>
                                    <InfoChip>
                                        {locationLabel(detail.location)}
                                    </InfoChip>
                                </div>

                                <section className="space-y-2">
                                    <h3 className="text-sm font-medium">
                                        Brief
                                    </h3>
                                    <p className="text-sm leading-7 whitespace-pre-wrap">
                                        {detail.brief?.context ??
                                            detail.goal ??
                                            'No brief yet.'}
                                    </p>
                                </section>

                                {detail.key_messages &&
                                detail.key_messages.length > 0 ? (
                                    <section className="space-y-2">
                                        <h3 className="text-sm font-medium">
                                            Key messages
                                        </h3>
                                        <ul className="list-disc space-y-1 pl-5 text-sm">
                                            {detail.key_messages.map(
                                                (message) => (
                                                    <li key={message}>
                                                        {message}
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    </section>
                                ) : null}

                                {detail.guidelines ? (
                                    <section className="space-y-2">
                                        <h3 className="text-sm font-medium">
                                            Guidelines
                                        </h3>
                                        <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                                            {detail.guidelines}
                                        </p>
                                    </section>
                                ) : null}
                            </div>
                        ) : null}

                        <DialogFooter>
                            <Button
                                type="button"
                                variant="accent"
                                className="rounded-pill"
                                onClick={() => void apply()}
                                disabled={saving || loading}
                            >
                                Apply
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            ) : null}
        </>
    );
}

export default memo(OpportunityCard);
