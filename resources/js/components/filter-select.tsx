import {
    createContext,
    useContext,
    useEffect,
    useId,
    useRef,
    useState,
    type ReactNode,
} from 'react';
import { ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';

export type FilterSelectItem = {
    value: string;
    label: string;
};

type FilterDropdownGroupContextValue = {
    openId: string | null;
    setOpenId: (id: string | null) => void;
};

const FilterDropdownGroupContext =
    createContext<FilterDropdownGroupContextValue | null>(null);

/** Wrap sibling filter dropdowns so only one menu is open at a time. */
export function FilterDropdownGroup({
    children,
    className,
}: {
    children: ReactNode;
    className?: string;
}) {
    const [openId, setOpenId] = useState<string | null>(null);
    const value = { openId, setOpenId };

    return (
        <FilterDropdownGroupContext.Provider value={value}>
            <div className={cn(className ?? 'flex flex-wrap items-center gap-3')}>
                {children}
            </div>
        </FilterDropdownGroupContext.Provider>
    );
}

type FilterDropdownProps = {
    label: string;
    icon: ReactNode;
    value: string;
    onChange: (value: string) => void;
    items: FilterSelectItem[];
    idleValue?: string;
    className?: string;
};

/** Custom h-14 pill dropdown — matches SearchPill height. */
export function FilterDropdown({
    label,
    icon,
    value,
    onChange,
    items,
    idleValue,
    className,
}: FilterDropdownProps) {
    const autoId = useId();
    const group = useContext(FilterDropdownGroupContext);
    const rootRef = useRef<HTMLDivElement>(null);
    const [localOpen, setLocalOpen] = useState(false);

    const open = group ? group.openId === autoId : localOpen;

    function setOpen(next: boolean) {
        if (group) {
            group.setOpenId(next ? autoId : null);
            return;
        }

        setLocalOpen(next);
    }

    useEffect(() => {
        if (!open) {
            return;
        }

        function close() {
            if (group) {
                group.setOpenId(null);
            } else {
                setLocalOpen(false);
            }
        }

        function onPointerDown(event: MouseEvent) {
            if (
                rootRef.current &&
                !rootRef.current.contains(event.target as Node)
            ) {
                close();
            }
        }

        function onKeyDown(event: KeyboardEvent) {
            if (event.key === 'Escape') {
                close();
            }
        }

        document.addEventListener('mousedown', onPointerDown);
        document.addEventListener('keydown', onKeyDown);

        return () => {
            document.removeEventListener('mousedown', onPointerDown);
            document.removeEventListener('keydown', onKeyDown);
        };
    }, [open, group]);

    const idle = idleValue ?? items[0]?.value;
    const active = value !== idle;
    const selected =
        items.find((item) => item.value === value)?.label ?? label;

    return (
        <div ref={rootRef} className={cn('relative', className)}>
            <button
                type="button"
                aria-haspopup="listbox"
                aria-expanded={open}
                aria-label={label}
                onClick={() => setOpen(!open)}
                className={cn(
                    'inline-flex h-14 items-center gap-2 rounded-pill border border-border px-4 text-sm font-medium transition-colors',
                    active
                        ? 'border-primary bg-primary text-primary-foreground'
                        : 'bg-card text-foreground hover:bg-muted',
                )}
            >
                <span
                    className={cn(
                        'shrink-0 [&_svg]:size-4',
                        active
                            ? 'text-primary-foreground'
                            : 'text-muted-foreground',
                    )}
                >
                    {icon}
                </span>
                <span className="max-w-36 truncate">{selected}</span>
                <ChevronDown
                    className={cn(
                        'size-4 shrink-0 transition-transform',
                        open && 'rotate-180',
                        active
                            ? 'text-primary-foreground'
                            : 'text-muted-foreground',
                    )}
                />
            </button>

            {open ? (
                <ul
                    role="listbox"
                    aria-label={label}
                    className="absolute top-[calc(100%+0.5rem)] left-0 z-50 max-h-72 min-w-full overflow-y-auto rounded-md border border-border bg-popover p-1 text-popover-foreground [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    {items.map((item) => {
                        const isSelected = item.value === value;

                        return (
                            <li
                                key={item.value}
                                role="option"
                                aria-selected={isSelected}
                            >
                                <button
                                    type="button"
                                    className={cn(
                                        'flex w-full items-center rounded-sm px-3 py-2 text-left text-sm whitespace-nowrap transition-colors',
                                        isSelected
                                            ? 'bg-primary text-primary-foreground'
                                            : 'text-foreground hover:bg-muted',
                                    )}
                                    onMouseDown={(event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        onChange(item.value);
                                        setOpen(false);
                                    }}
                                >
                                    {item.label}
                                </button>
                            </li>
                        );
                    })}
                </ul>
            ) : null}
        </div>
    );
}

/** @deprecated Prefer FilterDropdown — kept for import compatibility. */
export { FilterDropdown as FilterSelect };
