import { useEffect, useState } from 'react';
import { useSearchParams } from 'react-router';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { centsFromEuros, euros } from '@/company/pages/creators/format';
import { canManageMoney } from '@/lib/current-user';
import { ApiError, companyApi, http } from '@/lib/api';

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

export default function CompanyWalletPage() {
    const [searchParams] = useSearchParams();
    const [wallet, setWallet] = useState<Wallet | null>(null);
    const [transactions, setTransactions] = useState<WalletTransaction[]>([]);
    const canManage = canManageMoney();
    const [amount, setAmount] = useState('50');
    const [pendingId, setPendingId] = useState<number | null>(null);
    const [waiting, setWaiting] = useState(
        searchParams.get('topup') === 'success',
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
        if (!waiting && pendingId === null) {
            return;
        }

        const id = pendingId ?? transactions.find((row) => row.status === 'pending')?.id;

        if (!id) {
            return;
        }

        const timer = window.setInterval(() => {
            http.get<WalletTransaction>(companyApi.walletTopup(id))
                .then(({ data }) => {
                    if (data.status !== 'pending') {
                        setWaiting(false);
                        setPendingId(null);
                        void load();
                    }
                })
                .catch(() => undefined);
        }, 2000);

        return () => window.clearInterval(timer);
    }, [waiting, pendingId, transactions]);

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
            <div>
                <h1 className="text-2xl font-semibold tracking-tight">Wallet</h1>
                <p className="text-muted-foreground mt-1 text-sm">
                    Campaign funds are held here until a live post URL is
                    submitted.
                </p>
            </div>
            <InputError message={error ?? undefined} />
            {waiting && (
                <p className="text-muted-foreground text-sm">
                    Waiting for Stripe to confirm the top-up…
                </p>
            )}
            {wallet && (
                <div className="grid gap-4 sm:grid-cols-2">
                    <div className="border-border bg-card rounded-2xl border p-5">
                        <p className="text-muted-foreground text-sm">Available</p>
                        <p className="mt-2 text-3xl font-semibold">
                            {euros(wallet.available_cents)}
                        </p>
                    </div>
                    <div className="border-border bg-card rounded-2xl border p-5">
                        <p className="text-muted-foreground text-sm">Held</p>
                        <p className="mt-2 text-3xl font-semibold">
                            {euros(wallet.held_cents)}
                        </p>
                    </div>
                </div>
            )}
            {canManage && (
                <form
                    className="border-border bg-card grid max-w-md gap-3 rounded-2xl border p-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        void topup();
                    }}
                >
                    <Label htmlFor="topup">Top up (EUR)</Label>
                    <Input
                        id="topup"
                        type="number"
                        min="50"
                        step="1"
                        value={amount}
                        onChange={(event) => setAmount(event.target.value)}
                    />
                    <Button type="submit" disabled={saving}>
                        {saving ? 'Redirecting…' : 'Add funds'}
                    </Button>
                </form>
            )}
            <section className="grid gap-3">
                <h2 className="text-lg font-semibold">Ledger</h2>
                {transactions.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        No transactions yet.
                    </p>
                ) : (
                    <div className="grid gap-2">
                        {transactions.map((row) => (
                            <div
                                key={row.id}
                                className="border-border flex items-center justify-between rounded-xl border px-4 py-3 text-sm"
                            >
                                <div>
                                    <p className="font-medium">
                                        {row.type} · {row.status}
                                    </p>
                                    <p className="text-muted-foreground text-xs">
                                        {row.created_at
                                            ? new Date(
                                                  row.created_at,
                                              ).toLocaleString()
                                            : ''}
                                    </p>
                                </div>
                                <p>
                                    {row.direction === 'debit' ? '−' : '+'}
                                    {euros(row.amount_cents)}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}
