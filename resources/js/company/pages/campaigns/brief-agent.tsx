import { useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import { SoftCard } from '@/components/ds';
import { Button } from '@/components/ui/button';
import { ApiError, companyApi, http } from '@/lib/api';
import type { CampaignBrief } from './types';

export type BriefPatch = {
    path: string;
    before: string;
    after: string;
};

type ChatMessage = {
    id: string;
    role: 'user' | 'assistant';
    text: string;
    patches?: BriefPatch[];
};

type ChatResponse = {
    reply: string;
    brief: CampaignBrief;
    patches: BriefPatch[];
};

export default function CampaignBriefAgentPanel({
    campaignId,
    readOnly = false,
    getBrief,
    onBriefUpdated,
}: {
    campaignId: number;
    readOnly?: boolean;
    getBrief: () => CampaignBrief;
    onBriefUpdated: (brief: CampaignBrief, patches: BriefPatch[]) => void;
}) {
    const [messages, setMessages] = useState<ChatMessage[]>([
        {
            id: 'welcome',
            role: 'assistant',
            text: 'Ask me to change a field or draft missing sections. Edits land in the form — review them, then save.',
        },
    ]);
    const [input, setInput] = useState('');
    const [busy, setBusy] = useState(false);

    async function send(event: FormEvent) {
        event.preventDefault();

        const message = input.trim();

        if (message === '' || busy || readOnly) {
            return;
        }

        setInput('');
        setBusy(true);
        setMessages((current) => [
            ...current,
            { id: `user-${Date.now()}`, role: 'user', text: message },
        ]);

        try {
            const { data } = await http.post<ChatResponse>(
                companyApi.campaignBriefChat(campaignId),
                { message, brief: getBrief() },
            );

            setMessages((current) => [
                ...current,
                {
                    id: `assistant-${Date.now()}`,
                    role: 'assistant',
                    text: data.reply,
                    patches: data.patches ?? [],
                },
            ]);

            onBriefUpdated(data.brief, data.patches ?? []);
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'The brief agent could not reply.',
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <SoftCard
            title="Brief agent"
            className="flex h-full min-h-128 flex-col"
        >
            <div className="flex min-h-0 flex-1 flex-col gap-5">
                <div className="flex max-h-112 min-h-64 flex-1 flex-col gap-4 overflow-y-auto pr-1 scrollbar-none">
                    {messages.map((message) => (
                        <div key={message.id} className="space-y-2">
                            <div
                                className={
                                    message.role === 'user'
                                        ? 'ml-6 rounded-2xl bg-primary px-4 py-3 text-sm text-primary-foreground'
                                        : 'mr-4 rounded-2xl bg-muted px-4 py-3 text-sm text-foreground'
                                }
                            >
                                {message.text}
                            </div>
                            {message.role === 'assistant' &&
                            message.patches &&
                            message.patches.length > 0 ? (
                                <div className="mr-4 space-y-3 rounded-2xl border border-border bg-background p-4">
                                    <p className="text-xs font-medium text-muted-foreground">
                                        Updated in the form
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        {message.patches.map((patch) => (
                                            <span
                                                key={`${patch.path}-${patch.after.slice(0, 24)}`}
                                                className="rounded-pill bg-lime-soft px-3 py-1.5 text-xs font-medium text-lime-soft-foreground"
                                            >
                                                {friendlyPath(patch.path)}
                                            </span>
                                        ))}
                                    </div>
                                    <div className="space-y-2">
                                        {message.patches.map((patch) => (
                                            <div
                                                key={`${patch.path}-diff`}
                                                className="space-y-1 text-xs"
                                            >
                                                <p className="font-medium text-foreground">
                                                    {friendlyPath(patch.path)}
                                                </p>
                                                <p className="line-clamp-2 text-muted-foreground">
                                                    {truncate(patch.before) ||
                                                        '∅'}{' '}
                                                    →{' '}
                                                    {truncate(patch.after) ||
                                                        '∅'}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ) : null}
                        </div>
                    ))}
                    {busy ? (
                        <p className="mr-4 text-sm text-muted-foreground">
                            Editing…
                        </p>
                    ) : null}
                </div>

                <form
                    onSubmit={send}
                    className="flex gap-2 border-t border-border pt-4"
                >
                    <input
                        value={input}
                        onChange={(event) => setInput(event.target.value)}
                        disabled={busy || readOnly}
                        placeholder={
                            readOnly
                                ? 'Brief is locked'
                                : 'e.g. Tighten the key message…'
                        }
                        className="min-w-0 flex-1 rounded-pill border border-border bg-background px-4 py-3 text-sm outline-none placeholder:text-muted-foreground"
                    />
                    <Button
                        type="submit"
                        className="rounded-pill"
                        disabled={busy || readOnly || input.trim() === ''}
                    >
                        {busy ? '…' : 'Send'}
                    </Button>
                </form>
            </div>
        </SoftCard>
    );
}

function friendlyPath(path: string): string {
    const labels: Record<string, string> = {
        context: 'Context',
        product: 'Product',
        differentiators: 'Differentiators',
        target: 'Target',
        pains: 'Pains',
        trigger: 'Trigger',
        key_message: 'Key message',
        'audience.industries': 'Industries',
        'audience.geographies': 'Geographies',
        'audience.tone': 'Tone',
        'editorial.do': 'Do',
        'editorial.avoid': 'Avoid',
        references: 'References',
        angles: 'Angles',
    };

    if (labels[path]) {
        return labels[path];
    }

    if (path.startsWith('angles.')) {
        return `Angle ${Number(path.split('.')[1] ?? 0) + 1}`;
    }

    if (path.startsWith('pains.')) {
        return `Pain ${Number(path.split('.')[1] ?? 0) + 1}`;
    }

    if (path.startsWith('references.')) {
        return 'Reference';
    }

    return path.replaceAll('.', ' · ');
}

function truncate(value: string, max = 80): string {
    const trimmed = value.trim();

    if (trimmed.length <= max) {
        return trimmed;
    }

    return `${trimmed.slice(0, max)}…`;
}
