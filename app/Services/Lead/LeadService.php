<?php

namespace App\Services\Lead;

use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Services\Email\NotificationService;
use App\Support\LeadStatus;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            $lead = Lead::query()->create(array_merge($data, [
                'status' => LeadStatus::STATUS_NEW,
            ]));

            LeadStatusHistory::query()->create([
                'lead_id' => $lead->id,
                'from_status' => null,
                'to_status' => LeadStatus::STATUS_NEW,
            ]);

            $this->notifications->sendLeadNotifications($lead);

            return $lead->fresh(['expert', 'canton']);
        });
    }
}
