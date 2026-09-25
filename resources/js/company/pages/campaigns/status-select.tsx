import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ApiError } from '@/lib/api';
import { toast } from 'sonner';
import { useCampaigns } from './store';
import {
    actionForStatusChange,
    reachableStatuses,
    statusLabels,
    type CampaignStatus,
} from './types';

export default function CampaignStatusSelect({
    campaignId,
    status,
}: {
    campaignId: number;
    status: CampaignStatus;
}) {
    const transition = useCampaigns((state) => state.transition);
    const saving = useCampaigns((state) => state.saving);
    const options = reachableStatuses(status);

    if (status === 'completed') {
        return (
            <Badge variant="secondary" className="h-11 rounded-pill px-4">
                {statusLabels.completed}
            </Badge>
        );
    }

    async function change(next: string) {
        const action = actionForStatusChange(status, next as CampaignStatus);

        if (!action) {
            return;
        }

        try {
            await transition(campaignId, action);
            toast.success(
                `Status set to ${statusLabels[next as CampaignStatus].toLowerCase()}`,
            );
        } catch (caught) {
            toast.error(
                caught instanceof ApiError
                    ? caught.message
                    : 'Could not update status.',
            );
        }
    }

    return (
        <Select
            value={status}
            onValueChange={(value) => void change(value)}
            disabled={saving}
        >
            <SelectTrigger className="w-40 rounded-pill" aria-label="Campaign status">
                <SelectValue />
            </SelectTrigger>
            <SelectContent>
                {options.map((option) => (
                    <SelectItem key={option} value={option}>
                        {statusLabels[option]}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}
