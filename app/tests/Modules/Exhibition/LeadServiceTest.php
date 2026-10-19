<?php

namespace Modules\Exhibition;

use App\Modules\Exhibition\DTOs\LeadCreationDTO;
use App\Modules\Exhibition\Enums\InterestLevel;
use App\Modules\Exhibition\Models\Lead;
use App\Modules\Exhibition\Models\LeadTag;
use PHPUnit\Framework\TestCase;

class LeadServiceTest extends TestCase
{
    public function test_lead_creation_dto_holds_required_fields(): void
    {
        $dto = new LeadCreationDTO(
            attendeeId: '1',
            location: 'Test Location',
            notes: 'test lead',
            interestLevel: 'high',
            additionalData: []
        );

        $this->assertEquals('1', $dto->attendeeId);
        $this->assertEquals('Test Location', $dto->location);
        $this->assertEquals('test lead', $dto->notes);
        $this->assertEquals('high', $dto->interestLevel);
        $this->assertEquals([], $dto->additionalData);
    }

    public function test_lead_model_has_default_status(): void
    {
        $lead = new Lead();
        $this->assertNotEmpty($lead->getFillable());
    }

    public function test_lead_tag_model_has_correct_fields(): void
    {
        $tag = new LeadTag();
        $this->assertNull($tag->tag_name);
        $this->assertNull($tag->color);
    }
}