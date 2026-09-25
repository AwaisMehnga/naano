import { useEffect, useState, type ReactNode } from 'react';
import { SegmentedNav } from '@/components/ds';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';

export type CampaignBrief = {
    context: string;
    product: string;
    differentiators: string[];
    target: string;
    pains: string[];
    trigger: string;
    key_message: string;
    audience: {
        industries: string;
        geographies: string;
        tone: string;
    };
    editorial: {
        do: string[];
        avoid: string[];
    };
    references: { quote: string; structure: string }[];
    angles: { title: string; hook: string; format: string; example: string }[];
};

type BriefPane = 'strategy' | 'audience' | 'editorial' | 'angles';

const panes: { id: BriefPane; label: string }[] = [
    { id: 'strategy', label: 'Strategy' },
    { id: 'audience', label: 'Audience' },
    { id: 'editorial', label: 'Editorial' },
    { id: 'angles', label: 'Hooks' },
];

type CampaignBriefDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    companyName?: string | null;
    brief: CampaignBrief | null;
    loading?: boolean;
    error?: string | null;
};

/** Read-only full campaign brief for creators (strategy, audience, editorial, hooks). */
export function CampaignBriefDialog({
    open,
    onOpenChange,
    title,
    companyName,
    brief,
    loading = false,
    error = null,
}: CampaignBriefDialogProps) {
    const [pane, setPane] = useState<BriefPane>('strategy');

    useEffect(() => {
        if (open) {
            setPane('strategy');
        }
    }, [open]);

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="flex max-h-[85vh] w-full flex-col gap-0 overflow-hidden rounded-sm border-border p-0 sm:max-w-2xl">
                <DialogHeader className="shrink-0 space-y-2 border-b border-border px-6 py-5 pr-12 text-left">
                    <DialogDescription className="text-sm">
                        {companyName ?? 'Campaign brief'}
                    </DialogDescription>
                    <DialogTitle className="text-title font-medium tracking-tight text-balance">
                        {title}
                    </DialogTitle>
                </DialogHeader>

                <div className="shrink-0 overflow-x-auto border-b border-border px-6 py-4">
                    <SegmentedNav
                        items={panes}
                        value={pane}
                        onChange={(id) => setPane(id as BriefPane)}
                        className="w-max"
                    />
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto px-6 py-6">
                    {loading ? (
                        <p className="text-sm text-muted-foreground">
                            Loading brief…
                        </p>
                    ) : null}

                    {!loading && error ? (
                        <p className="text-sm text-destructive">{error}</p>
                    ) : null}

                    {!loading && !error && !brief ? (
                        <p className="text-sm text-muted-foreground">
                            No brief yet.
                        </p>
                    ) : null}

                    {!loading && !error && brief ? (
                        <>
                            {pane === 'strategy' ? (
                                <StrategyView brief={brief} />
                            ) : null}
                            {pane === 'audience' ? (
                                <AudienceView brief={brief} />
                            ) : null}
                            {pane === 'editorial' ? (
                                <EditorialView brief={brief} />
                            ) : null}
                            {pane === 'angles' ? (
                                <AnglesView brief={brief} />
                            ) : null}
                        </>
                    ) : null}
                </div>
            </DialogContent>
        </Dialog>
    );
}

function StrategyView({ brief }: { brief: CampaignBrief }) {
    const hasContent =
        brief.key_message.trim() !== '' ||
        brief.context.trim() !== '' ||
        brief.product.trim() !== '' ||
        brief.differentiators.length > 0 ||
        brief.target.trim() !== '' ||
        brief.pains.length > 0 ||
        brief.trigger.trim() !== '';

    if (!hasContent) {
        return <EmptyHint />;
    }

    return (
        <div className="space-y-8">
            {brief.key_message ? (
                <p className="border-l-2 border-primary pl-4 text-xl leading-snug font-medium tracking-tight text-balance">
                    {brief.key_message}
                </p>
            ) : null}
            <Block label="Context & objective" value={brief.context} />
            <Block label="Product" value={brief.product} />
            <ListBlock label="Differentiators" items={brief.differentiators} />
            <Block label="Target" value={brief.target} />
            <ListBlock label="Pains" items={brief.pains} numbered />
            <Block label="Trigger" value={brief.trigger} />
        </div>
    );
}

function AudienceView({ brief }: { brief: CampaignBrief }) {
    const hasContent =
        brief.audience.industries.trim() !== '' ||
        brief.audience.geographies.trim() !== '' ||
        brief.audience.tone.trim() !== '';

    if (!hasContent) {
        return <EmptyHint />;
    }

    return (
        <div className="space-y-8">
            <Block
                label="Target industries"
                value={brief.audience.industries}
            />
            <Block label="Geographies" value={brief.audience.geographies} />
            <Block label="Tone" value={brief.audience.tone} />
        </div>
    );
}

function EditorialView({ brief }: { brief: CampaignBrief }) {
    return (
        <div className="space-y-8">
            <div className="grid gap-8 md:grid-cols-2">
                <ListBlock label="Do" items={brief.editorial.do} />
                <ListBlock label="Avoid" items={brief.editorial.avoid} />
            </div>
            {brief.references.length > 0 ? (
                <section className="space-y-4">
                    <h3 className="text-sm font-medium">References</h3>
                    <div className="space-y-5">
                        {brief.references.map((reference, index) => (
                            <div
                                key={`${reference.quote}-${index}`}
                                className="space-y-2 rounded-2xl border border-border bg-muted px-4 py-4"
                            >
                                {reference.quote ? (
                                    <p className="text-base leading-relaxed italic">
                                        {reference.quote}
                                    </p>
                                ) : null}
                                {reference.structure ? (
                                    <p className="text-sm text-muted-foreground">
                                        {reference.structure}
                                    </p>
                                ) : null}
                            </div>
                        ))}
                    </div>
                </section>
            ) : (
                <EmptyHint />
            )}
        </div>
    );
}

function AnglesView({ brief }: { brief: CampaignBrief }) {
    if (brief.angles.length === 0) {
        return <EmptyHint />;
    }

    return (
        <div className="space-y-6">
            <p className="text-sm text-muted-foreground">
                Hooks and example posts you can rewrite in your own voice.
            </p>
            {brief.angles.map((angle, index) => (
                <article
                    key={`${angle.title}-${index}`}
                    className="space-y-3 rounded-2xl border border-border bg-muted px-4 py-4"
                >
                    <p className="text-xs font-medium tracking-[0.12em] text-muted-foreground uppercase">
                        Angle {String(index + 1).padStart(2, '0')}
                    </p>
                    {angle.title ? (
                        <h3 className="text-lg font-semibold tracking-tight">
                            {angle.title}
                        </h3>
                    ) : null}
                    {angle.hook ? (
                        <p className="text-base leading-snug font-medium">
                            {angle.hook}
                        </p>
                    ) : null}
                    {angle.format ? (
                        <p className="text-sm text-muted-foreground">
                            {angle.format}
                        </p>
                    ) : null}
                    {angle.example ? (
                        <p className="whitespace-pre-wrap text-sm leading-7">
                            {angle.example}
                        </p>
                    ) : null}
                </article>
            ))}
        </div>
    );
}

function Block({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    if (!value.trim()) {
        return null;
    }

    return (
        <section className="space-y-2">
            <h3 className="text-sm font-medium">{label}</h3>
            <p className="whitespace-pre-wrap text-sm leading-7 text-muted-foreground">
                {value}
            </p>
        </section>
    );
}

function ListBlock({
    label,
    items,
    numbered = false,
}: {
    label: string;
    items: string[];
    numbered?: boolean;
}) {
    if (items.length === 0) {
        return null;
    }

    return (
        <section className="space-y-2">
            <h3 className="text-sm font-medium">{label}</h3>
            <ul
                className={cn(
                    'space-y-1.5 pl-5 text-sm leading-7 text-muted-foreground',
                    numbered ? 'list-decimal' : 'list-disc',
                )}
            >
                {items.map((item) => (
                    <li key={item}>{item}</li>
                ))}
            </ul>
        </section>
    );
}

function EmptyHint(): ReactNode {
    return (
        <p className="text-sm text-muted-foreground">
            Nothing in this section yet.
        </p>
    );
}
