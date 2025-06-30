<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Eloquent;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\DatabaseNotificationCollection;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * 
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property bool $is_active
 * @property Carbon|null $last_activity_at
 * @property-read Collection<int, User> $friends
 * @property-read int|null $friends_count
 * @property-read Collection<int, User> $friendsOf
 * @property-read int|null $friends_of_count
 * @property-read DatabaseNotificationCollection<int, DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read Collection<int, Friendship> $pendingFriendRequests
 * @property-read int|null $pending_friend_requests_count
 * @property-read Collection<int, Friendship> $receivedFriendRequests
 * @property-read int|null $received_friend_requests_count
 * @property-read Collection<int, Friendship> $sentFriendRequests
 * @property-read int|null $sent_friend_requests_count
 * @property-read Collection<int, PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static UserFactory factory($count = null, $state = [])
 * @method static Builder<static>|User newModelQuery()
 * @method static Builder<static>|User newQuery()
 * @method static Builder<static>|User query()
 * @method static Builder<static>|User whereCreatedAt($value)
 * @method static Builder<static>|User whereEmail($value)
 * @method static Builder<static>|User whereEmailVerifiedAt($value)
 * @method static Builder<static>|User whereId($value)
 * @method static Builder<static>|User whereIsActive($value)
 * @method static Builder<static>|User whereLastActivityAt($value)
 * @method static Builder<static>|User whereName($value)
 * @method static Builder<static>|User wherePassword($value)
 * @method static Builder<static>|User whereRememberToken($value)
 * @method static Builder<static>|User whereUpdatedAt($value)
 * @mixin Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use Notifiable;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'last_activity_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_activity_at' => 'datetime',
        ];
    }

    public function sentFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'user_id');
    }

    public function receivedFriendRequests(): HasMany
    {
        return $this->hasMany(Friendship::class, 'friend_id');
    }

    public function friends(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    public function friendsOf(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'friendships', 'friend_id', 'user_id')
            ->wherePivot('status', 'accepted')
            ->withTimestamps();
    }

    public function allFriends()
    {
        return $this->friends->merge($this->friendsOf);
    }

    public function pendingFriendRequests(): HasMany
    {
        return $this->receivedFriendRequests()->where('status', 'pending');
    }

    public function hasFriendship($userId): bool
    {
        return $this->sentFriendRequests()
                ->where('friend_id', $userId)
                ->whereIn('status', ['pending', 'accepted'])
                ->exists()
            ||
            $this->receivedFriendRequests()
                ->where('user_id', $userId)
                ->whereIn('status', ['pending', 'accepted'])
                ->exists();
    }

    public function isFriendWith($userId): bool
    {
        return $this->sentFriendRequests()
                ->where('friend_id', $userId)
                ->where('status', 'accepted')
                ->exists()
            ||
            $this->receivedFriendRequests()
                ->where('user_id', $userId)
                ->where('status', 'accepted')
                ->exists();
    }
}
