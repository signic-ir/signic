<?php

namespace Modules\Exhibition;

use App.Modules\Exhibition\DTOs\LeadCreationDTO;
use App.Modules\Exhibition\Enums\InterestLevel;
use App.Modules\Exhibition\Enums\LeadSource;
use App.Modules\Exhibition\Enums\LeadStatus;
use App.Modules\Exhibition\Enums\LeadType;
use App.Modules\Exhibition\Models\Lead;
use App.Modules\Exhibition\Models\LeadTag;
use PHPUnit\Framework\TestCase;

class LeadServiceTest extends TestCase
{
    public function test_lead_creation_dto_holds_required_fields(): void
    {
        $dto = new LeadCreationDTO(
            exhibitorId: 1,
            attendeeId: 2,
            type: LeadType::VISITOR,
            source: LeadSource::SCAN,
            interestLevel: InterestLevel::HOT,
            metadata: ['notes' => 'test lead']
        );

        $this->assertEquals(1, $dto->exhibitorId);
        $this->assertEquals(2, $dto->attendeeId);
        $this->assertEquals(LeadType::VISITOR, $dto->type);
        $this->assertEquals(LeadSource::SCAN, $dto->source);
        $this->assertEquals(InterestLevel::HOT, $dto->interestLevel);
        $this->assertEquals(['notes' => 'test lead'], $dto->metadata);
    }

    public function test_lead_model_has_default_status(): void
    {
        $lead = new Lead();
        $this->assertEquals(LeadStatus::NEW, $lead->status);
    }

    public function test_lead_tag_model_has_correct_fields(): void
    {
        $tag = new LeadTag();
        $this->assertIsString($tag->name);
        $this->assertIsString($tag->slug);
    }
}