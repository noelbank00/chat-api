<?php

namespace App\Enums;

enum FriendshipStatusEnum: string
{
    case Accepted = 'accepted';
    case Pending = 'pending';
    case Rejected = 'rejected';
}
