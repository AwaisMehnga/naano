import { euros } from '@/company/pages/creators/format';
import type { ContractDocument } from '@/lib/contract';

export default function ContractDocumentView({
    contract,
}: {
    contract: ContractDocument;
}) {
    const terms = contract.terms;

    if (!terms) {
        return (
            <p className="text-muted-foreground text-sm">
                This contract has no terms yet.
            </p>
        );
    }

    return (
        <article className="bg-card border-border mx-auto max-w-2xl rounded-2xl border px-6 py-8 sm:px-10">
            <p className="text-muted-foreground text-sm">
                Platform contract · {contract.status}
            </p>
            <h1 className="mt-2 text-2xl font-semibold tracking-tight">
                {terms.campaign.name}
            </h1>
            <p className="text-muted-foreground mt-2 text-sm">
                {terms.brand.name} · {terms.creator.display_name}
            </p>
            <div className="mt-8 grid gap-6">
                <section>
                    <h2 className="text-sm font-semibold">Offer</h2>
                    <p className="mt-2 text-base">
                        {terms.offer.posts_count ?? '—'} post
                        {terms.offer.posts_count === 1 ? '' : 's'} ·{' '}
                        {euros(terms.offer.price_cents)} {terms.offer.currency}
                    </p>
                </section>
                <section>
                    <h2 className="text-sm font-semibold">Terms</h2>
                    <ol className="mt-3 grid gap-3">
                        {terms.clauses.map((clause, index) => (
                            <li key={index} className="flex gap-3 text-sm leading-6">
                                <span className="text-muted-foreground w-6 shrink-0">
                                    {String(index + 1).padStart(2, '0')}
                                </span>
                                <span>{clause}</span>
                            </li>
                        ))}
                    </ol>
                </section>
            </div>
        </article>
    );
}
