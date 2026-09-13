import { useEffect, useRef, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { ApiError, companyApi, creatorApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

type Message = {
    id: number;
    author: { id: number; name: string };
    body: string;
    read_at: string | null;
    created_at: string | null;
};

export default function CollaborationThread({
    collaborationId,
    side,
    canSend = true,
}: {
    collaborationId: number;
    side: 'company' | 'creator';
    canSend?: boolean;
}) {
    const [messages, setMessages] = useState<Message[]>([]);
    const [body, setBody] = useState('');
    const [busy, setBusy] = useState(false);
    const bottom = useRef<HTMLDivElement>(null);
    const userId = window.Naano?.user?.id;
    const paths =
        side === 'company'
            ? {
                  list: companyApi.collaborationMessages(collaborationId),
                  read: companyApi.collaborationMessagesRead(collaborationId),
              }
            : {
                  list: creatorApi.collaborationMessages(collaborationId),
                  read: creatorApi.collaborationMessagesRead(collaborationId),
              };

    async function load(markRead = false) {
        const { data } = await http.get<Message[]>(paths.list);
        setMessages(data);

        if (markRead && data.some((message) => message.author.id !== userId && message.read_at === null)) {
            const { data: next } = await http.post<Message[]>(paths.read);
            setMessages(next);
        }
    }

    useEffect(() => {
        load(true).catch((caught: unknown) => {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load messages.',
            );
        });

        const timer = window.setInterval(() => {
            load(true).catch(() => undefined);
        }, 10000);

        return () => window.clearInterval(timer);
    }, [collaborationId, side]);

    useEffect(() => {
        bottom.current?.scrollIntoView({ block: 'end' });
    }, [messages.length]);

    async function send(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!canSend || body.trim() === '') {
            return;
        }

        setBusy(true);

        try {
            await http.post<Message>(paths.list, { body: body.trim() });
            setBody('');
            await load();
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not send this message.',
            );
        } finally {
            setBusy(false);
        }
    }

    return (
        <div className="grid gap-3">
            <div className="border-border bg-muted/40 max-h-80 overflow-y-auto rounded-xl border p-3">
                {messages.length === 0 ? (
                    <p className="text-muted-foreground py-8 text-center text-sm">
                        No messages yet.
                    </p>
                ) : (
                    <div className="grid gap-2">
                        {messages.map((message) => {
                            const mine = message.author.id === userId;

                            return (
                                <article
                                    key={message.id}
                                    className={cn(
                                        'max-w-[85%] rounded-xl px-3 py-2 text-sm',
                                        mine
                                            ? 'bg-primary text-primary-foreground ml-auto'
                                            : 'bg-card border-border border',
                                    )}
                                >
                                    <p className="text-xs opacity-80">
                                        {message.author.name}
                                    </p>
                                    <p className="whitespace-pre-wrap">
                                        {message.body}
                                    </p>
                                </article>
                            );
                        })}
                        <div ref={bottom} />
                    </div>
                )}
            </div>
            {canSend ? (
                <form className="grid gap-2" onSubmit={(event) => void send(event)}>
                    <Textarea
                        value={body}
                        onChange={(event) => setBody(event.target.value)}
                        placeholder="Write a message…"
                        rows={3}
                    />
                    <Button type="submit" disabled={busy || body.trim() === ''}>
                        Send
                    </Button>
                </form>
            ) : (
                <p className="text-muted-foreground text-sm">
                    This collaboration can no longer receive messages.
                </p>
            )}
        </div>
    );
}
