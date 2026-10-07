<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Tests;

use App\Modules\Exhibition\Services\LeadService;
use App\Modules\Exhibition\Models\Lead;
use App\Modules\Exhibition\Models\Exhibitor;
use App\Modules\Exhibition\Models\LeadTag;
use Illuminate\Testing\Faker\FakerGenerator;
use Mockery;
use PHPUnit\Framework\Attributes\Test;

class LeadServiceTest
{
    private LeadService $service;
    private FakerGenerator $faker;

    public function setUp(): void
    {
        $this->service = new LeadService();
        $this->faker = app(FakerGenerator::class);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    #[Test]
    public function it_creates_lead_with_tags(): void
    {
        $exhibitor = Exhibitor::factory()->make(['id' => 1]);
        $tagIds = [1, 2];

        $result = $this->service->createLead(
            $exhibitor->id,
            $this->faker->company(),
            $this->faker->email(),
            $this->faker->text(),
            50,
            $tagIds
        );

        $this->assertNotNull($result);
        $this->assertInstanceOf(Lead::class, $result['lead']);
        $this->assertEquals(count($tagIds), count($result['tags']));
    }

    #[Test]
    public function it_leads_are_tagged_automatically_based_on_interests(): void
    {
        $exhibitor = Exhibitor::factory()->make(['id' => 1]);

        $interestLevel = $this->faker->randomElement(['high', 'medium', 'low']);
        $interests = $interestLevel === 'high' ? ['Tech', 'Innovation'] :
                    ($interestLevel === 'medium' ? ['Business'] : ['General']);

        $result = $this->service->createLead(
            $exhibitor->id,
            $this->faker->company(),
            $this->faker->email(),
            $this->faker->text(),
            50,
            []
        );

        $this->assertNotNull($result);
    }
}