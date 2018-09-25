<?php

namespace App\Events;

use App\Events;
use App\Events\Event;
use App\Modules\User\Controllers\UserController;
use App\Modules\User\Model\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

/**
 * Class TestEvent
 *
 * @package App\Events
 */
class TestEvent implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * TestEvent constructor.
     *
     * @param string $message - message
     */
    public function __construct($message)
    {

        $this->message = $message;
    }


    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        return ['my-channel'];
        /*
        $channels = [];
        $Users = User::where('id', '!=', 'null')->get();
        foreach ($Users as $user) {
            array_push($channels, new PrivateChannel('users.' . $user->id));
        }

        return $channels;

        //return new PrivateChannel('channel-name');
        */
    }
}
