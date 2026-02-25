<?php

namespace App\Listeners;

use App\Events\InactiveUsers;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;

class SendNotificationInactiveUsers implements ShouldQueue
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
    public function handle(InactiveUsers $event): void
    {
        $users = User::whereIn('id', $event->userIds)->where('status', 1)->get();
        $admins = User::role('admin')->where('status', 1)->get();

        $notifications = [];

        foreach ($admins as $admin) {
            foreach($users as $user) {
                $exists = Notification::where('subject', "User: {$user->first_name} {$user->last_name} has been inactive for more than 3 months")->where('user_id', $admin->id)->exists();

                if ($exists) {
                    continue;
                }

                $notifications[] = [
                    'user_id' => $admin->id,
                    'document_id' => 1,
                    'subject' => "User: {$user->first_name} {$user->last_name} has been inactive for more than 3 months",
                    'data' => json_encode([
                        'user_id' => $user->id,
                        'name' => $user->first_name . ' ' . $user->last_name,
                    ]),
                    'is_read' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }  
        }

        DB::transaction(function () use ($notifications) {
            Notification::insert($notifications);
        });
    }
}
