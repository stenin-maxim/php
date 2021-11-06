<?php

namespace App\Models\Recycler;

use App\Http\Controllers\Recycler\RecyclerProductionSpotController;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Производственная площадка утилизатора
 *
 * @property int $id
 * @property int $company_id
 * @property string|null $name
 * @property string|null $address
 * @property int $moderation_status,
 * @property string $moderator_comment,
 * @property int $creator_id
 * @property int|null $updater_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RecyclerProductionSpot extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'address',
        'company_id',
        'moderation_status',
        'moderator_comment',
        'creator_id',
        'updater_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
        'company_id',
        'creator_id',
        'updater_id',
        'created_at',
        'updated_at',
    ];

    /**
     * Bootstrap the model and its traits.
     */
    public static function boot(): void
    {
        parent::boot();

        $user = Auth::user();

        self::creating(function ($model) use ($user) {
            $model->company_id = $user->company_id;
            $model->moderation_status = MODERATION_STATUS_NEW;
            $model->creator_id = $user->id;
            $model->updated_at = null;
        });

        self::updating(function ($model) use ($user) {
            $model->updater_id = $user->id;
        });
    }

    /**
     * Get the recyclerProductionSpotPhotos for the ProductionSpot.
     */
    public function recyclerProductionSpotPhotos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecyclerProductionSpotPhoto::class);
    }

    public static function getSpotMainInfo(array $production_spot): array
    {
        $production_spot_main_info = [
            'name' => $production_spot['name'],
            'address' => $production_spot['address'],
            'main_photo' => null,
            'photos' => [],
        ];

        foreach ($production_spot['recycler_production_spot_photos'] as $key => $photo) {
            if ($photo['is_main'] === 1) {
                $production_spot_main_info['main_photo'] = action([RecyclerProductionSpotController::class, 'getResizedPhoto'], ['photo' => $photo['id']]);
            }
            $production_spot_main_info['photos'][$key]['id'] = $photo['id'];
            $production_spot_main_info['photos'][$key]['photo'] = action([RecyclerProductionSpotController::class, 'getResizedPhoto'], ['photo' => $photo['id']]);
        }

        return $production_spot_main_info;
    }
}
