import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';

export default function SpaHome({ title }: { title: string }) {
    return (
        <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto">
            <h1 className="text-xl font-medium">{title}</h1>
            <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                <div className="relative aspect-video overflow-hidden rounded-xl border border-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                </div>
                <div className="relative aspect-video overflow-hidden rounded-xl border border-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                </div>
                <div className="relative aspect-video overflow-hidden rounded-xl border border-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-foreground/20" />
                </div>
            </div>
        </div>
    );
}
