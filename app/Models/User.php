<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'timezone',
        'currency',
        'date_format',
        'theme',
        'notification_preferences',
        'preferred_wallet_1_id',
        'preferred_wallet_2_id',
        'preferred_wallet_3_id',
        'language',
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
            'notification_preferences' => 'array',
        ];
    }

    /**
     * Get the wallets for the user.
     */
    public function wallets()
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * Get the transactions for the user.
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the categories for the user.
     */
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    /**
     * Get the preferred wallets for dashboard display.
     */
    public function preferredWallet1()
    {
        return $this->belongsTo(Wallet::class, 'preferred_wallet_1_id');
    }

    public function preferredWallet2()
    {
        return $this->belongsTo(Wallet::class, 'preferred_wallet_2_id');
    }

    public function preferredWallet3()
    {
        return $this->belongsTo(Wallet::class, 'preferred_wallet_3_id');
    }

    /**
     * Get all preferred wallets as a collection.
     */
    public function getPreferredWalletsAttribute()
    {
        return collect([
            $this->preferredWallet1,
            $this->preferredWallet2,
            $this->preferredWallet3,
        ])->filter();
    }
}
