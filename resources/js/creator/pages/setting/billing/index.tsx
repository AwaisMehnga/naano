import { useEffect, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { api, ApiError, creatorApi } from '@/lib/api';

type Billing = {
    stripe_connect_id: string | null;
    payouts_enabled: boolean;
    bank_summary: string | null;
};

type Connect = {
    onboarded: boolean;
    payouts_enabled: boolean;
    stripe_connect_id: string | null;
};

type Wallet = {
    pending_cents: number;
    available_cents: number;
    in_transit_cents: number;
    paid_cents: number;
    currency: string;
    withdrawable: boolean;
};

function euros(cents: number): string {
    return `€${Math.round(cents / 100)}`;
}

export default function CreatorBillingPage() {
    const [billing, setBilling] = useState<Billing | null>(null);
    const [connect, setConnect] = useState<Connect | null>(null);
    const [wallet, setWallet] = useState<Wallet | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        Promise.all([
            api<Billing>(creatorApi.billing),
            api<Connect>(creatorApi.connect),
            api<Wallet>(creatorApi.wallet),
        ])
            .then(([nextBilling, nextConnect, nextWallet]) => {
                setBilling(nextBilling);
                setConnect(nextConnect);
                setWallet(nextWallet);
            })
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load billing.',
                );
            });
    }, []);

    async function startOnboarding() {
        setBusy(true);
        setError(null);

        try {
            const { url } = await api<{ url: string }>(
                creatorApi.connectOnboarding,
                { method: 'POST' },
            );
            window.location.assign(url);
        } catch (caught: unknown) {
            setBusy(false);
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not start Connect onboarding.',
            );
        }
    }

    async function withdraw() {
        if (!wallet || !wallet.withdrawable) {
            return;
        }

        setBusy(true);
        setError(null);

        try {
            await api(creatorApi.walletWithdrawals, {
                method: 'POST',
                body: JSON.stringify({ amount_cents: wallet.available_cents }),
            });
            const nextWallet = await api<Wallet>(creatorApi.wallet);
            setWallet(nextWallet);
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not request a withdrawal.',
            );
        } finally {
            setBusy(false);
        }
    }

    const payoutsEnabled = Boolean(
        connect?.payouts_enabled || billing?.payouts_enabled,
    );

    return (
        <div className="space-y-6">
            <Heading
                title="Payouts"
                description="Stripe Connect pays you by SEPA. Earnings credit when a live post URL is submitted."
            />
            <InputError message={error ?? undefined} />
            <Card>
                <CardHeader>
                    <CardTitle>Bank payouts</CardTitle>
                    <CardDescription>
                        {payoutsEnabled
                            ? 'Payouts are enabled.'
                            : 'Finish Stripe Connect to receive payouts.'}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        {billing?.bank_summary ??
                            (payoutsEnabled
                                ? 'Bank details are managed in Stripe.'
                                : 'No bank details saved')}
                    </p>
                    <Button
                        type="button"
                        onClick={() => void startOnboarding()}
                        disabled={busy}
                    >
                        Set up payouts
                    </Button>
                </CardContent>
            </Card>
            {wallet && (
                <Card>
                    <CardHeader>
                        <CardTitle>Earnings</CardTitle>
                        <CardDescription>
                            Available {euros(wallet.available_cents)} · pending{' '}
                            {euros(wallet.pending_cents)}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {wallet.withdrawable ? (
                            <Button
                                type="button"
                                onClick={() => void withdraw()}
                                disabled={busy}
                            >
                                Withdraw {euros(wallet.available_cents)}
                            </Button>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                Withdrawals open at €100 once Connect payouts
                                are enabled.
                            </p>
                        )}
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
