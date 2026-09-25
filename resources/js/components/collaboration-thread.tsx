import { useEffect, useState, type FormEvent, type KeyboardEvent } from 'react';
import { Clock, Send } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { ApiError, companyApi, creatorApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

type Message = {
    id: number;
    author: { id: number; name: string };
    body: string;
    read_at: string | null;
    created_at: string | null;
};

type ThreadMessage = Message & {
    clientId?: string;
    delivery?: 'pending' | 'sent';
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
    const [messages, setMessages] = useState<ThreadMessage[]>([]);
    const [body, setBody] = useState('');
    const userId = window.Naano?.user?.id;
    const userName = window.Naano?.user?.name ?? 'You';
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
        let next = data;

        if (
            markRead &&
            data.some(
                (message) =>
                    message.author.id !== userId && message.read_at === null,
            )
        ) {
            const marked = await http.post<Message[]>(paths.read);
            next = marked.data;
        }

        setMessages((current) => mergeThread(next, current));
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

    async function send(event?: FormEvent<HTMLFormElement>) {
        event?.preventDefault();

        const text = body.trim();

        if (!canSend || text === '' || userId === undefined) {
            return;
        }

        const clientId = crypto.randomUUID();
        const pending: ThreadMessage = {
            id: -Date.now(),
            clientId,
            delivery: 'pending',
            author: { id: userId, name: userName },
            body: text,
            read_at: null,
            created_at: new Date().toISOString(),
        };

        setBody('');
        setMessages((current) => [...current, pending]);

        try {
            const { data } = await http.post<Message>(paths.list, {
                body: text,
            });
            setMessages((current) =>
                current.map((item) =>
                    item.clientId === clientId
                        ? { ...data, delivery: 'sent' }
                        : item,
                ),
            );
        } catch (caught) {
            setMessages((current) =>
                current.filter((item) => item.clientId !== clientId),
            );
            setBody((current) => (current === '' ? text : current));
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not send this message.',
            );
        }
    }

    function onComposerKeyDown(event: KeyboardEvent<HTMLTextAreaElement>) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            void send();
        }
    }

    return (
        <div className="flex min-h-0 flex-1 flex-col">
            <div className="flex min-h-0 flex-1 flex-col-reverse overflow-y-auto px-4 py-3">
                {messages.length === 0 ? (
                    <p className="text-muted-foreground py-10 text-center text-sm">
                        No messages yet.
                    </p>
                ) : (
                    <div className="flex flex-col gap-1.5">
                        {messages.map((message, index) => {
                            const mine = message.author.id === userId;
                            const previous = messages[index - 1];
                            const stacked =
                                previous !== undefined &&
                                previous.author.id === message.author.id;

                            return (
                                <article
                                    key={message.clientId ?? message.id}
                                    className={cn(
                                        'max-w-[80%] rounded-lg px-3 py-2 text-sm',
                                        stacked ? 'mt-0' : 'mt-2 first:mt-0',
                                        mine
                                            ? 'bg-primary text-primary-foreground ml-auto rounded-br-sm'
                                            : 'bg-muted text-foreground mr-auto rounded-bl-sm',
                                    )}
                                >
                                    {!mine && !stacked && (
                                        <p className="text-muted-foreground mb-0.5 text-[11px] font-medium">
                                            {message.author.name}
                                        </p>
                                    )}
                                    <p className="whitespace-pre-wrap">
                                        {message.body}
                                    </p>
                                    <p
                                        className={cn(
                                            'mt-1 flex items-center gap-1 text-[10px] leading-none',
                                            mine
                                                ? 'text-primary-foreground/70 justify-end'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        <span>
                                            {formatTime(message.created_at)}
                                        </span>
                                        {mine &&
                                            (message.delivery === 'pending' ? (
                                                <Clock
                                                    className="size-3"
                                                    aria-label="Sending"
                                                />
                                            ) : (
                                                <span>sent</span>
                                            ))}
                                    </p>
                                </article>
                            );
                        })}
                    </div>
                )}
            </div>
            {canSend ? (
                <form
                    className="border-border shrink-0 border-t p-3"
                    onSubmit={(event) => void send(event)}
                >
                    <div className="border-input focus-within:border-ring focus-within:ring-ring/50 flex items-end rounded-xl border bg-card p-1 focus-within:ring-[3px]">
                        <textarea
                            value={body}
                            onChange={(event) => setBody(event.target.value)}
                            onKeyDown={onComposerKeyDown}
                            placeholder="Message"
                            rows={1}
                            className="placeholder:text-muted-foreground m-0 max-h-28 min-h-8 flex-1 resize-none border-0 bg-transparent px-3 py-1.5 text-sm leading-5 outline-none"
                        />
                        <Button
                            type="submit"
                            size="icon"
                            aria-label="Send"
                            disabled={body.trim() === ''}
                            className="size-8 shrink-0 rounded-lg"
                        >
                            <Send className="size-3.5" />
                        </Button>
                    </div>
                </form>
            ) : (
                <p className="text-muted-foreground shrink-0 border-t px-4 py-3 text-sm">
                    This collaboration can no longer receive messages.
                </p>
            )}
        </div>
    );
}

function mergeThread(
    server: Message[],
    current: ThreadMessage[],
): ThreadMessage[] {
    const pending = current.filter((item) => item.delivery === 'pending');
    const leftover = pending.filter(
        (item) =>
            !server.some(
                (row) =>
                    row.author.id === item.author.id && row.body === item.body,
            ),
    );

    return [
        ...server.map((row) => ({ ...row, delivery: 'sent' as const })),
        ...leftover,
    ];
}

function formatTime(iso: string | null): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);

    if (Number.isNaN(date.getTime())) {
        return '';
    }

    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}
