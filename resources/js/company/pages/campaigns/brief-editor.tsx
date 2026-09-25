import {
    useEffect,
    useState,
    type KeyboardEvent,
    type ReactNode,
} from 'react';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';
import { SegmentedNav, SoftCard } from '@/components/ds';
import { Button } from '@/components/ui/button';
import { ApiError, api, companyApi } from '@/lib/api';
import { cn } from '@/lib/utils';
import { useCampaigns } from './store';
import { emptyBrief, type CampaignBrief } from './types';

type BriefPane = 'strategy' | 'audience' | 'editorial' | 'angles';

type BriefLookups = {
    industries: string[];
    regions: string[];
    tones: string[];
};

const panes: { id: BriefPane; label: string }[] = [
    { id: 'strategy', label: 'Strategy' },
    { id: 'audience', label: 'Audience' },
    { id: 'editorial', label: 'Editorial' },
    { id: 'angles', label: 'Angles' },
];

export default function CampaignBriefEditor({
    campaignId,
    brief,
    readOnly = false,
    appliedBrief = null,
    onAppliedConsumed,
    onDraftChange,
    highlightPaths = [],
    onSaved,
}: {
    campaignId: number;
    brief: CampaignBrief | null;
    readOnly?: boolean;
    appliedBrief?: CampaignBrief | null;
    onAppliedConsumed?: () => void;
    onDraftChange?: (draft: CampaignBrief) => void;
    highlightPaths?: string[];
    onSaved?: () => void;
}) {
    const updateBrief = useCampaigns((state) => state.updateBrief);
    const saving = useCampaigns((state) => state.saving);
    const [pane, setPane] = useState<BriefPane>('strategy');
    const [draft, setDraft] = useState<CampaignBrief>(() => mergeBrief(brief));
    const [lookups, setLookups] = useState<BriefLookups>({
        industries: [],
        regions: [],
        tones: [],
    });

    function updateDraft(next: CampaignBrief) {
        setDraft(next);
        onDraftChange?.(next);
    }

    useEffect(() => {
        const next = mergeBrief(brief);
        setDraft(next);
        onDraftChange?.(next);
    }, [brief]);

    useEffect(() => {
        if (!appliedBrief) {
            return;
        }

        const next = mergeBrief(appliedBrief);
        setDraft(next);
        onDraftChange?.(next);
        onAppliedConsumed?.();
    }, [appliedBrief, onAppliedConsumed, onDraftChange]);

    useEffect(() => {
        if (highlightPaths.length === 0) {
            return;
        }

        const target = panes.find((item) =>
            paneHasHighlight(item.id, highlightPaths),
        );

        if (target) {
            setPane(target.id);
        }
    }, [highlightPaths]);

    useEffect(() => {
        api<{
            lookups: {
                industries?: string[];
                regions?: string[];
                tones?: string[];
            };
        }>(companyApi.audience)
            .then((data) => {
                setLookups({
                    industries: data.lookups.industries ?? [],
                    regions: data.lookups.regions ?? [],
                    tones: data.lookups.tones ?? [],
                });
            })
            .catch(() => {
                setLookups({ industries: [], regions: [], tones: [] });
            });
    }, []);

    async function save() {
        try {
            await updateBrief(campaignId, compactBrief(draft));
            onSaved?.();
            toast.success('Brief saved');
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save the brief.',
            );
        }
    }

    const highlightedCount = highlightPaths.length;

    return (
        <SoftCard
            title="Brief"
            action={
                <Button
                    type="button"
                    className="rounded-pill"
                    onClick={() => void save()}
                    disabled={saving || readOnly}
                >
                    {saving ? 'Saving…' : 'Save brief'}
                </Button>
            }
            className="min-w-0"
        >
            <div className="flex flex-col gap-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <SegmentedNav
                        items={panes}
                        value={pane}
                        onChange={(id) => setPane(id as BriefPane)}
                        className="w-fit max-w-full flex-wrap"
                    />
                    {highlightedCount > 0 ? (
                        <p className="rounded-pill bg-lime-soft px-4 py-2 text-sm text-lime-soft-foreground">
                            {highlightedCount} field
                            {highlightedCount === 1 ? '' : 's'} updated — review
                            then save
                        </p>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Context, constraints, and a few angles — not a
                            script.
                        </p>
                    )}
                </div>

                <div
                    className={cn(
                        'min-w-0 rounded-2xl border border-border bg-background p-5 sm:p-8',
                        highlightedCount > 0 && 'border-accent',
                    )}
                >
                    {pane === 'strategy' && (
                        <StrategyPane
                            draft={draft}
                            onChange={updateDraft}
                            highlightPaths={highlightPaths}
                        />
                    )}
                    {pane === 'audience' && (
                        <AudiencePane
                            draft={draft}
                            onChange={updateDraft}
                            lookups={lookups}
                            readOnly={readOnly}
                            highlightPaths={highlightPaths}
                        />
                    )}
                    {pane === 'editorial' && (
                        <EditorialPane
                            draft={draft}
                            onChange={updateDraft}
                            highlightPaths={highlightPaths}
                        />
                    )}
                    {pane === 'angles' && (
                        <AnglesPane
                            draft={draft}
                            onChange={updateDraft}
                            highlightPaths={highlightPaths}
                        />
                    )}
                </div>
            </div>
        </SoftCard>
    );
}

function StrategyPane({
    draft,
    onChange,
    highlightPaths,
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
    highlightPaths: string[];
}) {
    return (
        <div className="grid gap-10">
            <DocBlock
                kicker="Context & objective"
                hint="What the product does, who it is for, and what this 1–2 week push should change."
                highlighted={pathHighlighted(highlightPaths, 'context')}
            >
                <DocText
                    value={draft.context}
                    placeholder="One or two short paragraphs. Lead with proof, not invented outcomes."
                    className="min-h-36"
                    onChange={(context) => onChange({ ...draft, context })}
                />
            </DocBlock>
            <DocBlock
                kicker="Product"
                hint="The thing creators are actually talking about."
                highlighted={pathHighlighted(highlightPaths, 'product')}
            >
                <DocText
                    value={draft.product}
                    placeholder="A single paragraph. Concrete, not a slogan."
                    onChange={(product) => onChange({ ...draft, product })}
                />
            </DocBlock>
            <DocBlock
                kicker="Differentiators"
                hint="Enter adds a line. Empty backspace removes it."
                highlighted={pathHighlighted(highlightPaths, 'differentiators')}
            >
                <LineList
                    values={draft.differentiators}
                    placeholder="What only this product can show"
                    onChange={(differentiators) =>
                        onChange({ ...draft, differentiators })
                    }
                />
            </DocBlock>
            <DocBlock
                kicker="Target"
                hint="Industry, role, geography — one sentence is enough."
                highlighted={pathHighlighted(highlightPaths, 'target')}
            >
                <DocText
                    value={draft.target}
                    placeholder="Who should feel spoken to."
                    onChange={(target) => onChange({ ...draft, target })}
                />
            </DocBlock>
            <DocBlock
                kicker="Pains"
                hint="The buyer problems the post is allowed to name."
                highlighted={pathHighlighted(highlightPaths, 'pains')}
            >
                <LineList
                    values={draft.pains}
                    numbered
                    placeholder="A real pain, not a marketing claim"
                    onChange={(pains) => onChange({ ...draft, pains })}
                />
            </DocBlock>
            <DocBlock
                kicker="Trigger"
                hint="The moment someone starts looking."
                highlighted={pathHighlighted(highlightPaths, 'trigger')}
            >
                <DocText
                    value={draft.trigger}
                    placeholder="A team is hiring / evaluating / switching because…"
                    onChange={(trigger) => onChange({ ...draft, trigger })}
                />
            </DocBlock>
            <DocBlock
                kicker="Key message"
                hint="The line every post should be able to land on."
                highlighted={pathHighlighted(highlightPaths, 'key_message')}
            >
                <DocText
                    value={draft.key_message}
                    placeholder="See the work before you take the call."
                    className="min-h-16 border-l-2 border-primary pl-4 text-xl font-medium leading-snug"
                    onChange={(key_message) =>
                        onChange({ ...draft, key_message })
                    }
                />
            </DocBlock>
        </div>
    );
}

function AudiencePane({
    draft,
    onChange,
    lookups,
    readOnly,
    highlightPaths,
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
    lookups: BriefLookups;
    readOnly: boolean;
    highlightPaths: string[];
}) {
    return (
        <div className="grid gap-10">
            <DocBlock
                kicker="Industries"
                hint="Pick from the list. Stored as a comma-separated audience line."
                highlighted={pathHighlighted(
                    highlightPaths,
                    'audience.industries',
                )}
            >
                <LookupChips
                    options={lookups.industries}
                    value={draft.audience.industries}
                    disabled={readOnly}
                    onChange={(industries) =>
                        onChange({
                            ...draft,
                            audience: { ...draft.audience, industries },
                        })
                    }
                />
            </DocBlock>
            <DocBlock
                kicker="Geographies"
                hint="Where the posts should feel local."
                highlighted={pathHighlighted(
                    highlightPaths,
                    'audience.geographies',
                )}
            >
                <LookupChips
                    options={lookups.regions}
                    value={draft.audience.geographies}
                    disabled={readOnly}
                    onChange={(geographies) =>
                        onChange({
                            ...draft,
                            audience: { ...draft.audience, geographies },
                        })
                    }
                />
            </DocBlock>
            <DocBlock
                kicker="Tone"
                hint="How the creator should sound — peer to peer, not a brand page."
                highlighted={pathHighlighted(highlightPaths, 'audience.tone')}
            >
                <LookupChips
                    options={lookups.tones}
                    value={draft.audience.tone}
                    disabled={readOnly}
                    single
                    onChange={(tone) =>
                        onChange({
                            ...draft,
                            audience: { ...draft.audience, tone },
                        })
                    }
                />
            </DocBlock>
        </div>
    );
}

function LookupChips({
    options,
    value,
    onChange,
    disabled = false,
    single = false,
}: {
    options: string[];
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    single?: boolean;
}) {
    const selected = parseCsv(value);

    function toggle(option: string) {
        if (disabled) {
            return;
        }

        if (single) {
            onChange(selected.includes(option) ? '' : option);

            return;
        }

        const next = selected.includes(option)
            ? selected.filter((item) => item !== option)
            : [...selected, option];

        onChange(next.join(', '));
    }

    if (options.length === 0) {
        return (
            <p className="text-sm text-muted-foreground">
                Loading audience options…
            </p>
        );
    }

    return (
        <div className="flex flex-wrap gap-2">
            {options.map((option) => {
                const active = selected.includes(option);

                return (
                    <button
                        key={option}
                        type="button"
                        disabled={disabled}
                        onClick={() => toggle(option)}
                        className={cn(
                            'rounded-pill border px-4 py-2 text-sm font-medium transition-colors',
                            active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-card text-foreground hover:bg-muted',
                            disabled && 'opacity-60',
                        )}
                    >
                        {option}
                    </button>
                );
            })}
        </div>
    );
}

function parseCsv(value: string): string[] {
    return value
        .split(',')
        .map((item) => item.trim())
        .filter((item) => item !== '');
}

function EditorialPane({
    draft,
    onChange,
    highlightPaths,
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
    highlightPaths: string[];
}) {
    return (
        <div className="grid gap-10">
            <div className="grid gap-8 md:grid-cols-2">
                <DocBlock
                    kicker="Do"
                    hint="Facts, proof, CTA."
                    highlighted={pathHighlighted(highlightPaths, 'editorial.do')}
                >
                    <LineList
                        values={draft.editorial.do}
                        placeholder="Show real repos, stack, or project specifics"
                        onChange={(next) =>
                            onChange({
                                ...draft,
                                editorial: { ...draft.editorial, do: next },
                            })
                        }
                    />
                </DocBlock>
                <DocBlock
                    kicker="Avoid"
                    hint="Guardrails. What must never appear."
                    highlighted={pathHighlighted(
                        highlightPaths,
                        'editorial.avoid',
                    )}
                >
                    <LineList
                        values={draft.editorial.avoid}
                        placeholder="Never invent testimonials or numbers"
                        onChange={(next) =>
                            onChange({
                                ...draft,
                                editorial: { ...draft.editorial, avoid: next },
                            })
                        }
                    />
                </DocBlock>
            </div>
            <DocBlock
                kicker="References & inspiration"
                hint="A quote plus the structure behind it. This anchors specificity without scripting voice."
                highlighted={pathHighlighted(highlightPaths, 'references')}
            >
                <div className="grid gap-6">
                    {draft.references.map((reference, index) => (
                        <div
                            key={index}
                            className="grid gap-3 rounded-2xl border border-border bg-card p-5"
                        >
                            <DocText
                                value={reference.quote}
                                placeholder="Most developer portfolios show projects. Almost none show how someone actually thinks."
                                className="min-h-20 text-lg italic leading-relaxed"
                                onChange={(quote) => {
                                    const references = [...draft.references];
                                    references[index] = { ...reference, quote };
                                    onChange({ ...draft, references });
                                }}
                            />
                            <DocText
                                value={reference.structure}
                                placeholder="Structure: observation → proof on the platform → invite to explore."
                                className="min-h-16 text-sm text-muted-foreground"
                                onChange={(structure) => {
                                    const references = [...draft.references];
                                    references[index] = {
                                        ...reference,
                                        structure,
                                    };
                                    onChange({ ...draft, references });
                                }}
                            />
                        </div>
                    ))}
                    <Button
                        type="button"
                        variant="ghost"
                        className="w-fit px-0"
                        onClick={() =>
                            onChange({
                                ...draft,
                                references: [
                                    ...draft.references,
                                    { quote: '', structure: '' },
                                ],
                            })
                        }
                    >
                        <Plus className="size-4" />
                        Add a reference
                    </Button>
                </div>
            </DocBlock>
        </div>
    );
}

function AnglesPane({
    draft,
    onChange,
    highlightPaths,
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
    highlightPaths: string[];
}) {
    return (
        <div className="grid gap-6">
            <p className="text-sm text-muted-foreground">
                Each angle is a hypothesis for one post: a hook, a format, and a
                full example the creator can rewrite in their own voice.
            </p>
            {draft.angles.map((angle, index) => (
                <div
                    key={index}
                    className={cn(
                        'grid gap-4 rounded-2xl border border-border bg-card p-5 sm:p-6',
                        pathHighlighted(highlightPaths, `angles.${index}`) &&
                            'border-accent bg-lime-soft/40',
                    )}
                >
                    <p className="text-sm font-medium text-muted-foreground">
                        Angle {String(index + 1).padStart(2, '0')}
                    </p>
                    <DocText
                        value={angle.title}
                        placeholder="Technical proof over claims"
                        className="min-h-10 text-lg font-semibold"
                        onChange={(title) => {
                            const angles = [...draft.angles];
                            angles[index] = { ...angle, title };
                            onChange({ ...draft, angles });
                        }}
                    />
                    <DocText
                        value={angle.hook}
                        placeholder="Anyone can say they're a great developer. Not everyone can show the receipts."
                        className="min-h-16 text-base font-medium leading-snug"
                        onChange={(hook) => {
                            const angles = [...draft.angles];
                            angles[index] = { ...angle, hook };
                            onChange({ ...draft, angles });
                        }}
                    />
                    <DocText
                        value={angle.format}
                        placeholder="Thought leadership. Show real repos so skill can be verified."
                        className="min-h-12 text-sm text-muted-foreground"
                        onChange={(format) => {
                            const angles = [...draft.angles];
                            angles[index] = { ...angle, format };
                            onChange({ ...draft, angles });
                        }}
                    />
                    <div className="rounded-2xl bg-muted p-4">
                        <p className="mb-2 text-xs font-medium text-muted-foreground">
                            Post example
                        </p>
                        <DocText
                            value={angle.example}
                            placeholder="Write the post the way it should feel on LinkedIn. Creators will keep the idea, not the wording."
                            className="min-h-48 text-sm leading-6"
                            onChange={(example) => {
                                const angles = [...draft.angles];
                                angles[index] = { ...angle, example };
                                onChange({ ...draft, angles });
                            }}
                        />
                    </div>
                </div>
            ))}
            <Button
                type="button"
                variant="ghost"
                className="w-fit px-0"
                onClick={() =>
                    onChange({
                        ...draft,
                        angles: [
                            ...draft.angles,
                            { title: '', hook: '', format: '', example: '' },
                        ],
                    })
                }
            >
                <Plus className="size-4" />
                Add an angle
            </Button>
        </div>
    );
}

function DocBlock({
    kicker,
    hint,
    children,
    highlighted = false,
}: {
    kicker: string;
    hint?: string;
    children: ReactNode;
    highlighted?: boolean;
}) {
    return (
        <section
            className={cn(
                'grid gap-3 rounded-2xl transition-colors',
                highlighted &&
                    '-mx-2 border border-accent bg-lime-soft/40 px-4 py-4 sm:px-5',
            )}
        >
            <header>
                <div className="flex items-center gap-2">
                    <h3 className="text-sm font-semibold text-foreground">
                        {kicker}
                    </h3>
                    {highlighted ? (
                        <span className="rounded-pill bg-accent px-2.5 py-0.5 text-xs font-medium text-accent-foreground">
                            Updated
                        </span>
                    ) : null}
                </div>
                {hint ? (
                    <p className="mt-1 text-sm text-muted-foreground">{hint}</p>
                ) : null}
            </header>
            {children}
        </section>
    );
}

function DocText({
    value,
    onChange,
    className,
    placeholder,
}: {
    value: string;
    onChange: (value: string) => void;
    className?: string;
    placeholder?: string;
}) {
    return (
        <textarea
            value={value}
            placeholder={placeholder}
            onChange={(event) => onChange(event.target.value)}
            className={cn(
                'w-full resize-none bg-transparent p-0 text-base leading-7 outline-none placeholder:text-muted-foreground',
                className ?? 'min-h-24',
            )}
        />
    );
}

function LineList({
    values,
    onChange,
    placeholder,
    numbered = false,
}: {
    values: string[];
    onChange: (values: string[]) => void;
    placeholder?: string;
    numbered?: boolean;
}) {
    const items = values.length > 0 ? values : [''];

    function update(index: number, value: string) {
        const next = [...items];
        next[index] = value;
        onChange(next);
    }

    function onKeyDown(
        event: KeyboardEvent<HTMLTextAreaElement>,
        index: number,
    ) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const next = [...items];
            next.splice(index + 1, 0, '');
            onChange(next);

            return;
        }

        if (
            event.key === 'Backspace' &&
            items[index] === '' &&
            items.length > 1
        ) {
            event.preventDefault();
            onChange(items.filter((_, item) => item !== index));
        }
    }

    return (
        <ul className="grid gap-2">
            {items.map((value, index) => (
                <li key={index} className="flex items-start gap-3">
                    <span className="mt-1 w-16 shrink-0 text-sm text-muted-foreground">
                        {numbered ? `Pain #${index + 1}` : '–'}
                    </span>
                    <textarea
                        value={value}
                        placeholder={placeholder}
                        rows={1}
                        onChange={(event) => update(index, event.target.value)}
                        onKeyDown={(event) => onKeyDown(event, index)}
                        className="min-h-8 w-full resize-none bg-transparent p-0 text-base leading-7 outline-none placeholder:text-muted-foreground"
                    />
                </li>
            ))}
        </ul>
    );
}

function pathHighlighted(paths: string[], prefix: string): boolean {
    return paths.some(
        (path) => path === prefix || path.startsWith(`${prefix}.`),
    );
}

function paneHasHighlight(pane: BriefPane, paths: string[]): boolean {
    return paths.some((path) => {
        if (
            pane === 'strategy' &&
            (path === 'context' ||
                path === 'product' ||
                path === 'target' ||
                path === 'trigger' ||
                path === 'key_message' ||
                path.startsWith('differentiators') ||
                path.startsWith('pains'))
        ) {
            return true;
        }

        if (pane === 'audience' && path.startsWith('audience')) {
            return true;
        }

        if (
            pane === 'editorial' &&
            (path.startsWith('editorial') || path.startsWith('references'))
        ) {
            return true;
        }

        if (pane === 'angles' && path.startsWith('angles')) {
            return true;
        }

        return false;
    });
}

function nonempty(values: string[]): string[] {
    return values.map((value) => value.trim()).filter((value) => value !== '');
}

function compactBrief(draft: CampaignBrief): CampaignBrief {
    return {
        ...draft,
        differentiators: nonempty(draft.differentiators),
        pains: nonempty(draft.pains),
        editorial: {
            do: nonempty(draft.editorial.do),
            avoid: nonempty(draft.editorial.avoid),
        },
        references: draft.references.filter(
            (item) => item.quote.trim() !== '' || item.structure.trim() !== '',
        ),
        angles: draft.angles.filter(
            (item) =>
                item.title.trim() !== '' ||
                item.hook.trim() !== '' ||
                item.format.trim() !== '' ||
                item.example.trim() !== '',
        ),
    };
}

function mergeBrief(brief: CampaignBrief | null): CampaignBrief {
    const empty = emptyBrief();

    if (!brief) {
        return empty;
    }

    return {
        context: asString(brief.context),
        product: asString(brief.product),
        differentiators: stringList(brief.differentiators, empty.differentiators),
        target: asString(brief.target),
        pains: stringList(brief.pains, empty.pains),
        trigger: asString(brief.trigger),
        key_message: asString(brief.key_message),
        audience: {
            industries: asString(brief.audience?.industries),
            geographies: asString(brief.audience?.geographies),
            tone: asString(brief.audience?.tone),
        },
        editorial: {
            do: stringList(brief.editorial?.do, empty.editorial.do),
            avoid: stringList(brief.editorial?.avoid, empty.editorial.avoid),
        },
        references:
            brief.references?.length
                ? brief.references.map((item) => ({
                      quote: asString(item?.quote),
                      structure: asString(item?.structure),
                  }))
                : empty.references,
        angles: brief.angles?.length
            ? brief.angles.map((item) => ({
                  title: asString(item?.title),
                  hook: asString(item?.hook),
                  format: asString(item?.format),
                  example: asString(item?.example),
              }))
            : empty.angles,
    };
}

function asString(value: unknown): string {
    return typeof value === 'string' ? value : '';
}

function stringList(values: unknown, fallback: string[]): string[] {
    if (!Array.isArray(values) || values.length === 0) {
        return fallback;
    }

    return values.map((value) => asString(value));
}
