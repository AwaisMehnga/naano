import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';
import { AppLink } from '@/components/app-link';
import { MetricStat, SoftCard } from '@/components/ds';
import { InfoChip } from '@/components/info-chip';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsFromEuros, euros } from '@/company/pages/creators/format';
import { canManageMoney } from '@/lib/current-user';
import { ApiError, companyApi, http } from '@/lib/api';
import { cn } from '@/lib/utils';

type Wallet = {
    available_cents: number;
    held_cents: number;
    currency: string;
    campaign_holds: { campaign_id: number; held_cents: number }[];
};

type WalletTransaction = {
    id: number;
    type: string;
    direction: string;
    amount_cents: number;
    status: string;
    campaign_id: number | null;
    collaboration_id: number | null;
    stripe_id: string | null;
    created_at: string | null;
};

const typeLabels: Record<string, string> = {
    topup: 'Top-up',
    hold: 'Hold',
    release: 'Release',
    payout: 'Payout',
    refund: 'Refund',
    charge: 'Charge',
};

const statusLabels: Record<string, string> = {
    pending: 'Pending',
    posted: 'Posted',
    failed: 'Failed',
};

const PENDING_TOPUP_KEY = 'wallet_pending_topup_id';

function readPendingTopupId(): number | null {
    const raw = sessionStorage.getItem(PENDING_TOPUP_KEY);

    if (!raw) {
        return null;
    }

    const id = Number(raw);

    return Number.isFinite(id) && id > 0 ? id : null;
}

function clearPendingTopupId(): void {
    sessionStorage.removeItem(PENDING_TOPUP_KEY);
}

export default function CompanyWalletPage() {
    const [searchParams] = useSearchParams();
    const [wallet, setWallet] = useState<Wallet | null>(null);
    const [transactions, setTransactions] = useState<WalletTransaction[]>([]);
    const canManage = canManageMoney();
    const [amount, setAmount] = useState('50');
    const [pendingId, setPendingId] = useState<number | null>(() =>
        readPendingTopupId(),
    );
    const [waiting, setWaiting] = useState(
        () =>
            searchParams.get('topup') === 'success' ||
            readPendingTopupId() !== null,
    );
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    async function load() {
        const [{ data: nextWallet }, { data: nextTransactions }] =
            await Promise.all([
                http.get<Wallet>(companyApi.wallet),
                http.get<WalletTransaction[]>(companyApi.walletTransactions()),
            ]);

        setWallet(nextWallet);
        setTransactions(nextTransactions);
    }

    useEffect(() => {
        load().catch((caught: unknown) => {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load the wallet.',
            );
        });
    }, []);

    useEffect(() => {
        if (!waiting || wallet === null) {
            return;
        }

        const pending =
            pendingId ??
            transactions.find(
                (row) => row.type === 'topup' && row.status === 'pending',
            )?.id;

        if (pending) {
            if (pendingId !== pending) {
                setPendingId(pending);
            }

            return;
        }

        // Returned from Stripe and the ledger already shows a posted top-up.
        setWaiting(false);
        setPendingId(null);
        clearPendingTopupId();
    }, [waiting, wallet, transactions, pendingId]);

    useEffect(() => {
        if (!waiting || pendingId === null) {
            return;
        }

        const timer = window.setInterval(() => {
            http.get<WalletTransaction>(companyApi.walletTopup(pendingId))
                .then(({ data }) => {
                    if (data.status !== 'pending') {
                        setWaiting(false);
                        setPendingId(null);
                        clearPendingTopupId();
                        void load();
                    }
                })
                .catch(() => undefined);
        }, 2000);

        return () => window.clearInterval(timer);
    }, [waiting, pendingId]);

    async function topup() {
        const amountCents = centsFromEuros(amount);

        if (amountCents === undefined) {
            setError('Enter a valid amount.');

            return;
        }

        setSaving(true);
        setError(null);

        try {
            const { data } = await http.post<{
                checkout_url: string;
                wallet_transaction_id: number;
            }>(companyApi.walletTopups, { amount_cents: amountCents });
            sessionStorage.setItem(
                PENDING_TOPUP_KEY,
                String(data.wallet_transaction_id),
            );
            setPendingId(data.wallet_transaction_id);
            window.location.href = data.checkout_url;
        } catch (caught) {
            setSaving(false);
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not start a top-up.',
            );
        }
    }

    return (
        <div className="flex w-full flex-1 flex-col gap-8">
            <div className="space-y-2">
                <h1 className="text-heading font-medium tracking-tight">
                    Wallet
                </h1>
                <p className="max-w-xl text-sm text-muted-foreground">
                    Campaign funds sit here until a live post URL is submitted.
                </p>
            </div>

            <InputError message={error ?? undefined} />

            {waiting ? (
                <SoftCard className="border-transparent bg-accent">
                    <p className="text-sm text-accent-foreground">
                        Waiting for Stripe to confirm the top-up…
                    </p>
                </SoftCard>
            ) : null}

            {wallet ? (
                <div className="grid gap-5 lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
                    <SoftCard className="border-transparent bg-accent">
                        <MetricStat
                            value={euros(wallet.available_cents)}
                            label="Available"
                            hint={
                                <span className="text-accent-foreground/70">
                                    Ready to book creators · {wallet.currency}
                                </span>
                            }
                            className="text-accent-foreground [&_p]:text-accent-foreground"
                        />
                    </SoftCard>

                    <SoftCard>
                        <MetricStat
                            value={euros(wallet.held_cents)}
                            label="Held"
                            hint="Locked on booked collaborations until posts go live."
                        />
                    </SoftCard>
                </div>
            ) : null}

            {wallet && wallet.campaign_holds.length > 0 ? (
                <SoftCard title="Campaign holds">
                    <div className="grid gap-3">
                        {wallet.campaign_holds.map((hold) => (
                            <div
                                key={hold.campaign_id}
                                className="flex items-center justify-between gap-3 rounded-2xl border border-border bg-background px-4 py-3"
                            >
                                <AppLink
                                    href={`/campaigns/${hold.campaign_id}`}
                                    className="text-sm font-medium underline-offset-4 hover:underline"
                                >
                                    Campaign #{hold.campaign_id}
                                </AppLink>
                                <p className="text-sm font-medium">
                                    {euros(hold.held_cents)}
                                </p>
                            </div>
                        ))}
                    </div>
                </SoftCard>
            ) : null}

            {canManage ? (
                <SoftCard title="Add funds">
                    <form
                        className="flex gap-4 items-center"
                        onSubmit={(event) => {
                            event.preventDefault();
                            void topup();
                        }}
                    >
                        <div className="grid gap-2">
                            <Label htmlFor="topup">Amount (EUR)</Label>
                            <Input
                                id="topup"
                                type="number"
                                min="50"
                                step="1"
                                value={amount}
                                onChange={(event) =>
                                    setAmount(event.target.value)
                                }
                                className="rounded-pill"
                            />
                            <p className="text-xs text-muted-foreground">
                                Minimum €50. You’ll finish payment with Stripe.
                            </p>
                        </div>
                        <Button
                            type="submit"
                            variant="accent"
                            className="w-fit rounded-pill"
                            disabled={saving}
                        >
                            {saving ? 'Redirecting…' : 'Add funds'}
                        </Button>
                    </form>
                </SoftCard>
            ) : null}

            <SoftCard title="Ledger">
                {transactions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No transactions yet.
                    </p>
                ) : (
                    <div className="grid gap-3">
                        {transactions.map((row) => {
                            const credit = row.direction !== 'debit';

                            return (
                                <div
                                    key={row.id}
                                    className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-border bg-background px-4 py-3"
                                >
                                    <div className="min-w-0 space-y-2">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-medium">
                                                {typeLabels[row.type] ??
                                                    row.type}
                                            </p>
                                            <InfoChip>
                                                {statusLabels[row.status] ??
                                                    row.status}
                                            </InfoChip>
                                        </div>
                                        <p className="text-xs text-muted-foreground">
                                            {row.created_at
                                                ? new Date(
                                                      row.created_at,
                                                  ).toLocaleString()
                                                : '—'}
                                        </p>
                                    </div>
                                    <p
                                        className={cn(
                                            'text-sm font-semibold tabular-nums',
                                            credit
                                                ? 'text-foreground'
                                                : 'text-muted-foreground',
                                        )}
                                    >
                                        {credit ? '+' : '−'}
                                        {euros(row.amount_cents)}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                )}
            </SoftCard>
        </div>
    );
}
