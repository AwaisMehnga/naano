<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

class CampaignFitAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return <<<'INSTRUCTIONS'
            You score how well a LinkedIn creator fits a B2B campaign.

            Use only the creator and campaign facts in the prompt. Do not invent audience size, industries, or locations.
            fit_score: 0–100 overall fit for this creator to execute the brief.
            audience_relevance: 0–100 how well the creator's audience matches the campaign ICP and target.
            reasons: one to five short phrases a creator would understand (niche, region, audience, or brief overlap).
            Score below 30 when the creator's niches or audience clearly miss the brief.
            INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'fit_score' => $schema->integer()->min(0)->max(100)->required(),
            'audience_relevance' => $schema->integer()->min(0)->max(100)->required(),
            'reasons' => $schema->array()
                ->min(0)
                ->max(5)
                ->items($schema->string())
                ->required(),
        ];
    }
}
