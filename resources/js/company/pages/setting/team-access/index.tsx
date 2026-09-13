import { useEffect, useState, type FormEvent } from 'react';
import { toast } from 'sonner';
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
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { canManageMoney } from '@/lib/current-user';
import { api, ApiError, companyApi } from '@/lib/api';

type Member = {
    id: number;
    name: string | null;
    email: string | null;
    role: 'owner' | 'member';
    joined_at: string | null;
    status: 'joined' | 'pending';
};

export default function CompanyTeamAccessPage() {
    const [members, setMembers] = useState<Member[]>([]);
    const canManage = canManageMoney();
    const [email, setEmail] = useState('');
    const [role, setRole] = useState<'owner' | 'member'>('member');
    const [open, setOpen] = useState(false);
    const [error, setError] = useState<string | null>(null);

    async function load() {
        setMembers(await api<Member[]>(companyApi.members));
    }

    useEffect(() => {
        load().catch((caught: unknown) => {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not load team.',
            );
        });
    }, []);

    async function invite(event: FormEvent) {
        event.preventDefault();
        setError(null);
        try {
            const member = await api<Member>(companyApi.members, {
                method: 'POST',
                body: JSON.stringify({ email, role }),
            });
            setEmail('');
            setOpen(false);
            await load();
            toast.success(
                member.status === 'pending' ? 'Invite sent.' : 'Member added.',
            );
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not invite that person.',
            );
        }
    }

    async function changeRole(member: Member, next: 'owner' | 'member') {
        setError(null);
        try {
            await api<Member>(companyApi.member(member.id), {
                method: 'PATCH',
                body: JSON.stringify({ role: next }),
            });
            await load();
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not change role.',
            );
        }
    }

    async function remove(member: Member) {
        setError(null);
        try {
            await api(companyApi.member(member.id), { method: 'DELETE' });
            await load();
        } catch (caught: unknown) {
            setError(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not remove member.',
            );
        }
    }

    return (
        <div className="space-y-6">
            <Heading
                title="Team access"
                description="Members can brief, search, and review. Only owners top up, book, and manage billing."
            />
            <InputError message={error ?? undefined} />
            <Card>
                <CardHeader className="flex flex-row items-center justify-between gap-4">
                    <div>
                        <CardTitle>Workspace members</CardTitle>
                        <CardDescription>
                            Invite by email. New people get a link to create a
                            company account and join this workspace.
                        </CardDescription>
                    </div>
                    {canManage && (
                        <Dialog open={open} onOpenChange={setOpen}>
                            <DialogTrigger asChild>
                                <Button>Invite</Button>
                            </DialogTrigger>
                            <DialogContent>
                                <form className="space-y-4" onSubmit={invite}>
                                    <DialogHeader>
                                        <DialogTitle>Invite a member</DialogTitle>
                                        <DialogDescription>
                                            We&apos;ll add them if they already
                                            have a company account, or email an
                                            invite if they don&apos;t.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={email}
                                            onChange={(event) =>
                                                setEmail(event.target.value)
                                            }
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Role</Label>
                                        <Select
                                            value={role}
                                            onValueChange={(value) =>
                                                setRole(
                                                    value as 'owner' | 'member',
                                                )
                                            }
                                        >
                                            <SelectTrigger className="w-full">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="member">
                                                    Member
                                                </SelectItem>
                                                <SelectItem value="owner">
                                                    Owner
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <DialogFooter>
                                        <Button type="submit">Send invite</Button>
                                    </DialogFooter>
                                </form>
                            </DialogContent>
                        </Dialog>
                    )}
                </CardHeader>
                <CardContent className="space-y-3">
                    {members.map((member) => (
                        <div
                            key={`${member.status}-${member.id}`}
                            className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-3"
                        >
                            <div>
                                <p className="font-medium">
                                    {member.name ?? member.email}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {member.email}
                                    {member.status === 'pending' &&
                                        ' · Invite pending'}
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                {member.status === 'pending' ? (
                                    <span className="text-sm capitalize text-muted-foreground">
                                        {member.role}
                                    </span>
                                ) : canManage ? (
                                    <Select
                                        value={member.role}
                                        onValueChange={(value) =>
                                            changeRole(
                                                member,
                                                value as 'owner' | 'member',
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-32">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="owner">
                                                Owner
                                            </SelectItem>
                                            <SelectItem value="member">
                                                Member
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                ) : (
                                    <span className="text-sm capitalize text-muted-foreground">
                                        {member.role}
                                    </span>
                                )}
                                {canManage && member.status === 'joined' && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => remove(member)}
                                    >
                                        Remove
                                    </Button>
                                )}
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>
        </div>
    );
}
