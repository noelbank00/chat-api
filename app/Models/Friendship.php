<?php

namespace App\Models;

use App\Enums\FriendshipStatusEnum;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property int $friend_id
 * @property FriendshipStatusEnum $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $friend
 * @property-read User $user
 * @method static Builder<static>|Friendship newModelQuery()
 * @method static Builder<static>|Friendship newQuery()
 * @method static Builder<static>|Friendship query()
 * @method static Builder<static>|Friendship whereCreatedAt($value)
 * @method static Builder<static>|Friendship whereFriendId($value)
 * @method static Builder<static>|Friendship whereId($value)
 * @method static Builder<static>|Friendship whereStatus($value)
 * @method static Builder<static>|Friendship whereUpdatedAt($value)
 * @method static Builder<static>|Friendship whereUserId($value)
 * @mixin Eloquent
 */
class Friendship extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'friend_id',
        'status',
    ];

    public function casts(): array
    {
        return [
            'status' => FriendshipStatusEnum::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function friend(): BelongsTo
    {
        return $this->belongsTo(User::class, 'friend_id');
    }
}
