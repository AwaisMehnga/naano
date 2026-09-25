<?php

namespace App\Ai\Agents;

use App\Ai\BriefDocument;
use App\Ai\Tools\EditBriefTool;
use App\Ai\Tools\ReadBriefTool;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Promptable;
use Stringable;

class CampaignBriefAgent implements Agent, HasTools
{
    use Promptable;

    /**
     * @param  list<string>  $industries
     * @param  list<string>  $regions
     * @param  list<string>  $tones
     */
    public function __construct(
        private BriefDocument $document,
        private array $industries = [],
        private array $regions = [],
        private array $tones = [],
    ) {}

    public function instructions(): Stringable|string
    {
        $industries = implode(', ', $this->industries);
        $regions = implode(', ', $this->regions);
        $tones = implode(', ', $this->tones);

        return <<<INSTRUCTIONS
            You help a company edit a LinkedIn creator campaign brief for Naano.

            The brief is a single JSON document. The UI updates live when you edit; the user clicks Save when ready. Never tell them to copy or paste JSON.

            ## Editing constraints
            - Always call read_brief before any edit_brief.
            - Always use edit_brief for surgical updates. Never rewrite the whole brief.
            - Prefer the smallest correct change. Leave untouched fields alone.
            - old_value must exactly match what read_brief returned for that path.
            - For list fields (differentiators, pains, editorial.do, editorial.avoid, references, angles), edit a single index (e.g. pains.0) or replace the whole list with JSON only when necessary.
            - audience.industries, audience.geographies, and audience.tone must be comma-separated values chosen only from:
              industries: {$industries}
              geographies (regions): {$regions}
              tones: {$tones}
            - Ground claims in company website text, value proposition, and ICPs. Do not invent metrics, customers, or products.
            - Keep copy short and concrete. Creators need context, constraints, a CTA, and example angles — not a script.
            - After edits, briefly tell the user what you changed. Do not ask them to accept changes.
            INSTRUCTIONS;
    }

    public function tools(): iterable
    {
        return [
            new ReadBriefTool($this->document),
            new EditBriefTool($this->document),
        ];
    }
}
