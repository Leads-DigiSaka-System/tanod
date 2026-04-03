<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Register all of the event broadcasting channels that your application
| supports. The given channel authorization callbacks are used to check
| if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('notifications.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('ticket.{ticketId}', function ($user, $ticketId) {
    $ticket = \App\Models\Ticket::find($ticketId);

    if (! $ticket) {
        return false;
    }

    // Admins, submitter, or assigned TPS can listen
    return $user->hasAnyRole(['super-admin', 'sub-admin'])
        || $ticket->submitted_by === $user->id
        || $ticket->assigned_to === $user->id
        || $ticket->assignees()->where('user_id', $user->id)->exists();
});
