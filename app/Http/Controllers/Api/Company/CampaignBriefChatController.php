<?php

namespace App\Http\Controllers\Api\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Company\ChatCompanyCampaignBriefRequest;
use App\Models\Campaign;
use App\Services\CampaignBriefChatService;
use App\Services\CurrentCompanyService;
use App\Support\AjaxResponse;
use Illuminate\Http\JsonResponse;

class CampaignBriefChatController extends Controller
{
    public function __construct(
        private CampaignBriefChatService $chat,
        private CurrentCompanyService $currentCompany,
    ) {}

    public function store(ChatCompanyCampaignBriefRequest $request, Campaign $campaign): JsonResponse
    {
        $validated = $request->validated();

        return AjaxResponse::success($this->chat->chat(
            $this->currentCompany->fromRequest($request),
            $campaign,
            (string) $validated['message'],
            isset($validated['brief']) && is_array($validated['brief'])
                ? $validated['brief']
                : null,
        ));
    }
}
