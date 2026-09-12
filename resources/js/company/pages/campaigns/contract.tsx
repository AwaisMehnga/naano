import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router';
import { ArrowLeft } from 'lucide-react';
import ContractDocumentView from '@/components/contract-document';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { ApiError, companyApi, http } from '@/lib/api';
import type { ContractDocument } from '@/lib/contract';

export default function CompanyContractPage() {
    const { id } = useParams();
    const collaborationId = Number(id);
    const navigate = useNavigate();
    const [contract, setContract] = useState<ContractDocument | null>(null);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        if (!Number.isFinite(collaborationId) || collaborationId < 1) {
            return;
        }

        http.get<ContractDocument>(
            companyApi.collaborationContract(collaborationId),
        )
            .then(({ data }) => setContract(data))
            .catch((caught: unknown) => {
                setError(
                    caught instanceof ApiError
                        ? caught.message
                        : 'Could not load the contract.',
                );
            });
    }, [collaborationId]);

    return (
        <div className="mx-auto flex w-full max-w-3xl flex-1 flex-col gap-6 p-4 lg:p-6">
            <Button
                type="button"
                variant="ghost"
                className="w-fit px-0"
                onClick={() => void navigate(-1)}
            >
                <ArrowLeft className="size-4" />
                Back
            </Button>
            <InputError message={error ?? undefined} />
            {contract && <ContractDocumentView contract={contract} />}
        </div>
    );
}
