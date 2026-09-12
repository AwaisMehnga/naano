import { create } from 'zustand';
import { ApiError, companyApi, http } from '@/lib/api';
import type {
    CampaignBrief,
    CampaignDetail,
    CampaignList,
    CampaignObjective,
    CampaignStatus,
    CampaignType,
    CollaborationRow,
    PipelineTab,
    StatusAction,
} from './types';

type CampaignsState = {
    list: CampaignList | null;
    listStatus: CampaignStatus | '';
    campaign: CampaignDetail | null;
    collaborations: CollaborationRow[];
    pipeline: PipelineTab;
    loading: boolean;
    saving: boolean;
    error: string | null;
    fetchList: (query?: {
        status?: CampaignStatus | '';
        page?: number;
        q?: string;
    }) => Promise<void>;
    fetchCampaign: (id: number) => Promise<void>;
    fetchCollaborations: (id: number, pipeline?: PipelineTab) => Promise<void>;
    create: (payload: {
        name: string;
        type: CampaignType;
        objective: CampaignObjective;
        budget_cents?: number;
        company_icp_id?: number;
    }) => Promise<CampaignDetail>;
    updateBrief: (id: number, brief: CampaignBrief) => Promise<void>;
    transition: (
        id: number,
        action: StatusAction,
    ) => Promise<void>;
    invite: (campaignId: number, creatorProfileId: number) => Promise<void>;
    select: (campaignId: number, collaborationId: number) => Promise<void>;
    cancelCollab: (
        campaignId: number,
        collaborationId: number,
    ) => Promise<void>;
};

function messageFrom(caught: unknown, fallback: string): string {
    return caught instanceof ApiError ? caught.message : fallback;
}

const transitionUrl: Record<StatusAction, (id: number) => string> = {
    launch: companyApi.campaignLaunch,
    resume: companyApi.campaignResume,
    reopen: companyApi.campaignReopen,
    pause: companyApi.campaignPause,
    complete: companyApi.campaignComplete,
    cancel: companyApi.campaignCancel,
};

export const useCampaigns = create<CampaignsState>((set, get) => ({
    list: null,
    listStatus: '',
    campaign: null,
    collaborations: [],
    pipeline: 'all',
    loading: false,
    saving: false,
    error: null,

    async fetchList(query = {}) {
        set({ loading: true, error: null, listStatus: query.status ?? '' });

        try {
            const { data } = await http.get<CampaignList>(
                companyApi.campaigns({
                    status: query.status || undefined,
                    page: query.page,
                    q: query.q,
                }),
            );
            set({ list: data, loading: false });
        } catch (caught) {
            set({
                loading: false,
                error: messageFrom(caught, 'Could not load campaigns.'),
            });
        }
    },

    async fetchCampaign(id) {
        set({ loading: true, error: null });

        try {
            const { data } = await http.get<CampaignDetail>(
                companyApi.campaign(id),
            );
            set({ campaign: data, loading: false });
        } catch (caught) {
            set({
                loading: false,
                campaign: null,
                error: messageFrom(caught, 'Could not load this campaign.'),
            });
        }
    },

    async fetchCollaborations(id, pipeline = get().pipeline) {
        set({ pipeline });

        try {
            const { data } = await http.get<CollaborationRow[]>(
                companyApi.campaignCollaborations(id, { pipeline }),
            );
            set({ collaborations: data });
        } catch (caught) {
            set({
                error: messageFrom(caught, 'Could not load collaborations.'),
            });
        }
    },

    async create(payload) {
        const { data } = await http.post<CampaignDetail>(
            companyApi.campaigns(),
            payload,
        );

        return data;
    },

    async updateBrief(id, brief) {
        set({ saving: true, error: null });

        try {
            const { data } = await http.patch<CampaignDetail>(
                companyApi.campaign(id),
                { brief },
            );
            set({ campaign: data, saving: false });
        } catch (caught) {
            set({
                saving: false,
                error: messageFrom(caught, 'Could not save the brief.'),
            });
            throw caught;
        }
    },

    async transition(id, action) {
        set({ saving: true, error: null });

        try {
            const { data } = await http.post<CampaignDetail>(
                transitionUrl[action](id),
            );
            set({ campaign: data, saving: false });
        } catch (caught) {
            set({
                saving: false,
                error: messageFrom(caught, 'Could not update campaign status.'),
            });
            throw caught;
        }
    },

    async invite(campaignId, creatorProfileId) {
        await http.post(companyApi.campaignInvites(campaignId), {
            creator_profile_id: creatorProfileId,
        });
        await Promise.all([
            get().fetchCampaign(campaignId),
            get().fetchCollaborations(campaignId),
        ]);
    },

    async select(campaignId, collaborationId) {
        await http.post(companyApi.collaborationSelect(collaborationId));
        await Promise.all([
            get().fetchCampaign(campaignId),
            get().fetchCollaborations(campaignId),
        ]);
    },

    async cancelCollab(campaignId, collaborationId) {
        await http.post(companyApi.collaborationCancel(collaborationId));
        await Promise.all([
            get().fetchCampaign(campaignId),
            get().fetchCollaborations(campaignId),
        ]);
    },
}));
