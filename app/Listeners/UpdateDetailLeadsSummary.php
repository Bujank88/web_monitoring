<?php

namespace App\Listeners;

use App\Events\LeadsMasterUpdated;
use App\Http\Controllers\LeadsMasterController;

class UpdateDetailLeadsSummary
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(LeadsMasterUpdated $event): void
    {
        // Update summary record saat ada perubahan di leads_master
        $controller = new LeadsMasterController();
        $controller->updateSummaryRecord($event->lead->id);
    }
}
