<?php

namespace Database\Seeders;

use App\Models\Status;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Status::truncate();

         $statuses = [
            // document
            ['module' => 'document', 'code' => 'PENDING',   'label' => 'Pending'],
            ['module' => 'document', 'code' => 'ARCHIVED',  'label' => 'Archived'],
            ['module' => 'document', 'code' => 'COMPLETED', 'label' => 'Completed'],
            ['module' => 'document', 'code' => 'DELAYED',   'label' => 'Delayed'],
            ['module' => 'document', 'code' => 'RELEASED',  'label' => 'Released'],

            // document_assignment
            ['module' => 'document_assignment', 'code' => 'PENDING',      'label' => 'Pending'],
            ['module' => 'document_assignment', 'code' => 'ACKNOWLEDGED', 'label' => 'Acknowledged'],
            ['module' => 'document_assignment', 'code' => 'APPROVED',     'label' => 'Approved'],
            ['module' => 'document_assignment', 'code' => 'SIGNED',       'label' => 'Signed'],
            ['module' => 'document_assignment', 'code' => 'REVIEWED',     'label' => 'Reviewed'],
            ['module' => 'document_assignment', 'code' => 'RESPONDED',    'label' => 'Responded'],
            ['module' => 'document_assignment', 'code' => 'COMPLETED',    'label' => 'Completed'],
            ['module' => 'document_assignment', 'code' => 'DELAYED',      'label' => 'Delayed'],

            // document_tracking
            ['module' => 'document_tracking', 'code' => 'ROUTED',     'label' => 'Routed'],
            ['module' => 'document_tracking', 'code' => 'COMPLETED',  'label' => 'Completed'],
            ['module' => 'document_tracking', 'code' => 'RETURNED',   'label' => 'Returned'],
            ['module' => 'document_tracking', 'code' => 'APPROVED',   'label' => 'Approved'],

            // document drafts
            ['module' => 'document_draft', 'code' => 'PENDING',  'label' => 'Pending'],
            ['module' => 'document_draft', 'code' => 'IN_REVIEW',  'label' => 'In Review'],
            ['module' => 'document_draft', 'code' => 'APPROVED', 'label' => 'Approved'],
        ];


        foreach ($statuses as $status) {
            Status::firstOrCreate(
                ['module' => $status['module'], 'code' => $status['code']],
                ['label' => $status['label'], 'is_active' => true]
            );
        }
    }
}
