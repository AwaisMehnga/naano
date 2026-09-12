import { useState, type FormEvent } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { api, ApiError, creatorApi } from '@/lib/api';

export default function CreatorAccountPage() {
    const [password, setPassword] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [saving, setSaving] = useState(false);

    async function onSubmit(event: FormEvent) {
        event.preventDefault();
        setSaving(true);
        setError(null);
        try {
            await api(creatorApi.account, {
                method: 'DELETE',
                body: JSON.stringify({ password }),
            });
            window.location.href = '/';
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not delete account.',
            );
            setSaving(false);
        }
    }

    return (
        <div className="space-y-6">
            <Heading
                title="Account"
                description="Password and two-factor stay in account security. Deleting is permanent."
            />
            <Card>
                <CardHeader>
                    <CardTitle>Security</CardTitle>
                    <CardDescription>
                        Change your password on the account security page.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="outline" asChild>
                        <a href="/settings/security">Open security settings</a>
                    </Button>
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>Delete account</CardTitle>
                    <CardDescription>
                        This logs you out and removes your creator login.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form className="max-w-sm space-y-4" onSubmit={onSubmit}>
                        <div className="space-y-2">
                            <Label htmlFor="password">Current password</Label>
                            <Input
                                id="password"
                                type="password"
                                value={password}
                                onChange={(event) =>
                                    setPassword(event.target.value)
                                }
                                required
                            />
                        </div>
                        <InputError message={error ?? undefined} />
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={saving}
                        >
                            {saving ? 'Deleting…' : 'Delete account'}
                        </Button>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}
