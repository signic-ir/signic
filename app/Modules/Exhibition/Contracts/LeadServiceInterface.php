<?php

declare(strict_types=1);

namespace App\Modules\Exhibition\Contracts;

use App\Modules\Exhibition\DTOs\LeadCreationDTO;
use App\Modules\Exhibition\Models\Lead;

interface LeadServiceInterface
{
    public function createLead(int $exhibitorId, LeadCreationDTO $dto): Lead;

    public function getLeads(int $exhibitorId, array $filters = []): \Illuminate\Support\Collection;

    public function exportLeads(int $exhibitorId): \Illuminate\Support\Collection;
}