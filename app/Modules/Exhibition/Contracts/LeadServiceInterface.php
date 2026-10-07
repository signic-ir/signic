<?php

declare(strict_types=1);

namespace App\Exhibition\Contracts;

use App\Exhibition\DTOs\LeadCreationDTO;
use App\Exhibition\Models\Lead;

interface LeadServiceInterface
{
    public function createLead(int $exhibitorId, LeadCreationDTO $dto): Lead;

    public function getLeads(int $exhibitorId, array $filters = []): \Illuminate\Support\Collection;

    public function exportLeads(int $exhibitorId): \Illuminate\Support\Collection;
}