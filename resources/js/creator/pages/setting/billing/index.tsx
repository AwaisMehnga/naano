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

export default function CreatorBillingPage() {
    const [billing, setBilling] = useState<Billing | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        api<Billing>(creatorApi.billing)
            .then(setBilling)
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load billing.',
                );
            });
    }, []);

    const hasBank = Boolean(billing?.bank_summary || billing?.payouts_enabled);

    return (
        <div className="space-y-6">
            <Heading
                title="Company and billing"
                description="Payout details for Stripe Connect. Bank setup ships with payments."
            />
            <InputError message={error ?? undefined} />
            <Card>
                <CardHeader>
                    <CardTitle>Billing details</CardTitle>
                    <CardDescription>
                        {hasBank ? 'Billing details saved.' : 'No billing details saved.'}
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div>
                        <h3 className="font-medium">Bank details</h3>
                        <p className="text-sm text-muted-foreground">
                            Your saved payout details.
                        </p>
                        <p className="mt-2 text-sm">
                            {billing?.bank_summary ?? 'No bank details saved'}
                        </p>
                    </div>
                    <Button type="button" disabled>
                        Add your details
                    </Button>
                    <p className="text-sm text-muted-foreground">
                        Add your details to receive bank transfers. Stripe Connect
                        onboarding is enabled in the payments slice.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
