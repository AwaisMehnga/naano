import CollaborationThread from '@/components/collaboration-thread';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';

export default function CollaborationChatSheet({
    open,
    onOpenChange,
    collaborationId,
    title,
    side,
    canSend = true,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    collaborationId: number | null;
    title: string;
    side: 'company' | 'creator';
    canSend?: boolean;
}) {
    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="w-full gap-0 p-0 sm:max-w-md"
            >
                <SheetHeader className="border-border shrink-0 border-b pr-12">
                    <SheetTitle>{title}</SheetTitle>
                </SheetHeader>
                {collaborationId !== null && (
                    <CollaborationThread
                        collaborationId={collaborationId}
                        side={side}
                        canSend={canSend}
                    />
                )}
            </SheetContent>
        </Sheet>
    );
}
