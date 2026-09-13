import {
    useEffect,
    useState,
    type KeyboardEvent,
    type ReactNode,
} from 'react';
import { Plus } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { ApiError } from '@/lib/api';
import { cn } from '@/lib/utils';
import { useCampaigns } from './store';
import { emptyBrief, type CampaignBrief } from './types';

type BriefPane = 'strategy' | 'audience' | 'editorial' | 'angles';

const panes: { id: BriefPane; label: string; hint: string }[] = [
    { id: 'strategy', label: 'Strategy', hint: 'Context, product, pains, message' },
    { id: 'audience', label: 'Audience', hint: 'Who this is for, and the tone' },
    { id: 'editorial', label: 'Editorial', hint: 'Do, avoid, references' },
    { id: 'angles', label: 'Angles', hint: 'Hooks and example posts' },
];

export default function CampaignBriefEditor({
    campaignId,
    brief,
    readOnly = false,
}: {
    campaignId: number;
    brief: CampaignBrief | null;
    readOnly?: boolean;
}) {
    const updateBrief = useCampaigns((state) => state.updateBrief);
    const saving = useCampaigns((state) => state.saving);
    const [pane, setPane] = useState<BriefPane>('strategy');
    const [draft, setDraft] = useState<CampaignBrief>(() => mergeBrief(brief));

    useEffect(() => {
        setDraft(mergeBrief(brief));
    }, [brief]);

    async function save() {
        try {
            await updateBrief(campaignId, compactBrief(draft));
            toast.success('Brief saved');
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not save the brief.',
            );
        }
    }

    return (
        <div className="grid gap-6 lg:grid-cols-[13rem_minmax(0,1fr)]">
            <aside className="lg:sticky lg:top-4 lg:self-start">
                <p className="text-muted-foreground mb-3 text-sm">
                    Brief for creators. Keep it light: context, constraints, a
                    CTA, and a few example posts — not a script.
                </p>
                <nav className="grid gap-1">
                    {panes.map((item) => (
                        <button
                            key={item.id}
                            type="button"
                            className={cn(
                                'rounded-lg px-3 py-2 text-left',
                                pane === item.id
                                    ? 'bg-secondary text-foreground'
                                    : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                            )}
                            onClick={() => setPane(item.id)}
                        >
                            <span className="block text-sm font-medium">
                                {item.label}
                            </span>
                            <span className="block text-xs">{item.hint}</span>
                        </button>
                    ))}
                </nav>
                <Button
                    type="button"
                    className="mt-4 w-full"
                    onClick={() => void save()}
                    disabled={saving || readOnly}
                >
                    {saving ? 'Saving…' : 'Save brief'}
                </Button>
            </aside>
            <article className="bg-card min-w-0 rounded-2xl border border-border px-5 py-6 sm:px-8 sm:py-8">
                {pane === 'strategy' && (
                    <StrategyPane draft={draft} onChange={setDraft} />
                )}
                {pane === 'audience' && (
                    <AudiencePane draft={draft} onChange={setDraft} />
                )}
                {pane === 'editorial' && (
                    <EditorialPane draft={draft} onChange={setDraft} />
                )}
                {pane === 'angles' && (
                    <AnglesPane draft={draft} onChange={setDraft} />
                )}
            </article>
        </div>
    );
}

function StrategyPane({
    draft,
    onChange,
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
}) {
    return (
        <div className="grid gap-10">
            <DocBlock
                kicker="Context & objective"
                hint="What the product does, who it is for, and what this 1–2 week push should change."
            >
                <DocText
                    value={draft.context}
                    placeholder="One or two short paragraphs. Lead with proof, not invented outcomes."
                    className="min-h-36"
                    onChange={(context) => onChange({ ...draft, context })}
                />
            </DocBlock>
            <DocBlock kicker="Product" hint="The thing creators are actually talking about.">
                <DocText
                    value={draft.product}
                    placeholder="A single paragraph. Concrete, not a slogan."
                    onChange={(product) => onChange({ ...draft, product })}
                />
            </DocBlock>
            <DocBlock kicker="Differentiators" hint="Enter adds a line. Empty backspace removes it.">
                <LineList
                    values={draft.differentiators}
                    placeholder="What only this product can show"
                    onChange={(differentiators) =>
                        onChange({ ...draft, differentiators })
                    }
                />
            </DocBlock>
            <DocBlock kicker="Target" hint="Industry, role, geography — one sentence is enough.">
                <DocText
                    value={draft.target}
                    placeholder="Who should feel spoken to."
                    onChange={(target) => onChange({ ...draft, target })}
                />
            </DocBlock>
            <DocBlock kicker="Pains" hint="The buyer problems the post is allowed to name.">
                <LineList
                    values={draft.pains}
                    numbered
                    placeholder="A real pain, not a marketing claim"
                    onChange={(pains) => onChange({ ...draft, pains })}
                />
            </DocBlock>
            <DocBlock kicker="Trigger" hint="The moment someone starts looking.">
                <DocText
                    value={draft.trigger}
                    placeholder="A team is hiring / evaluating / switching because…"
                    onChange={(trigger) => onChange({ ...draft, trigger })}
                />
            </DocBlock>
            <DocBlock kicker="Key message" hint="The line every post should be able to land on.">
                <DocText
                    value={draft.key_message}
                    placeholder="See the work before you take the call."
                    className="border-primary min-h-16 border-l-2 pl-4 text-xl font-medium leading-snug"
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
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
}) {
    return (
        <div className="grid gap-10">
            <DocBlock kicker="Target industries">
                <DocText
                    value={draft.audience.industries}
                    placeholder="B2B, Developer Tools, Software"
                    className="min-h-12"
                    onChange={(industries) =>
                        onChange({
                            ...draft,
                            audience: { ...draft.audience, industries },
                        })
                    }
                />
            </DocBlock>
            <DocBlock kicker="Target geographies">
                <DocText
                    value={draft.audience.geographies}
                    placeholder="Europe"
                    className="min-h-12"
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
            >
                <DocText
                    value={draft.audience.tone}
                    placeholder="Direct, confident, short sentences. No corporate buzzwords."
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

function EditorialPane({
    draft,
    onChange,
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
}) {
    return (
        <div className="grid gap-10">
            <div className="grid gap-8 md:grid-cols-2">
                <DocBlock kicker="Do" hint="Facts, proof, CTA.">
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
                <DocBlock kicker="Avoid" hint="Guardrails. What must never appear.">
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
            >
                <div className="grid gap-6">
                    {draft.references.map((reference, index) => (
                        <div key={index} className="grid gap-3">
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
                                className="text-muted-foreground min-h-16 text-sm"
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
}: {
    draft: CampaignBrief;
    onChange: (draft: CampaignBrief) => void;
}) {
    return (
        <div className="grid gap-8">
            <p className="text-muted-foreground text-sm">
                Each angle is a hypothesis for one post: a hook, a format, and a
                full example the creator can rewrite in their own voice.
            </p>
            {draft.angles.map((angle, index) => (
                <div key={index} className="grid gap-4">
                    <p className="text-muted-foreground text-sm">
                        {String(index + 1).padStart(2, '0')}
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
                        className="text-muted-foreground min-h-12 text-sm"
                        onChange={(format) => {
                            const angles = [...draft.angles];
                            angles[index] = { ...angle, format };
                            onChange({ ...draft, angles });
                        }}
                    />
                    <div className="bg-muted/50 rounded-xl p-4">
                        <p className="text-muted-foreground mb-2 text-xs">
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
}: {
    kicker: string;
    hint?: string;
    children: ReactNode;
}) {
    return (
        <section className="grid gap-3">
            <header>
                <h3 className="text-sm font-semibold">{kicker}</h3>
                {hint && (
                    <p className="text-muted-foreground mt-1 text-sm">{hint}</p>
                )}
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
                'placeholder:text-muted-foreground w-full resize-none bg-transparent p-0 text-base leading-7 outline-none',
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

    function onKeyDown(event: KeyboardEvent<HTMLTextAreaElement>, index: number) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const next = [...items];
            next.splice(index + 1, 0, '');
            onChange(next);

            return;
        }

        if (event.key === 'Backspace' && items[index] === '' && items.length > 1) {
            event.preventDefault();
            onChange(items.filter((_, item) => item !== index));
        }
    }

    return (
        <ul className="grid gap-2">
            {items.map((value, index) => (
                <li key={index} className="flex items-start gap-3">
                    <span className="text-muted-foreground mt-1 w-16 shrink-0 text-sm">
                        {numbered ? `Pain #${index + 1}` : '–'}
                    </span>
                    <textarea
                        value={value}
                        placeholder={placeholder}
                        rows={1}
                        onChange={(event) => update(index, event.target.value)}
                        onKeyDown={(event) => onKeyDown(event, index)}
                        className="placeholder:text-muted-foreground min-h-8 w-full resize-none bg-transparent p-0 text-base leading-7 outline-none"
                    />
                </li>
            ))}
        </ul>
    );
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
        ...empty,
        ...brief,
        differentiators: brief.differentiators?.length
            ? brief.differentiators
            : empty.differentiators,
        pains: brief.pains?.length ? brief.pains : empty.pains,
        audience: { ...empty.audience, ...brief.audience },
        editorial: {
            do: brief.editorial?.do?.length
                ? brief.editorial.do
                : empty.editorial.do,
            avoid: brief.editorial?.avoid?.length
                ? brief.editorial.avoid
                : empty.editorial.avoid,
        },
        references: brief.references?.length
            ? brief.references
            : empty.references,
        angles: brief.angles?.length ? brief.angles : empty.angles,
    };
}
