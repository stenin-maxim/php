<?php

namespace App\Models\Recycler;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Фото помещения
 *
 * @property int $id
 * @property int $recycler_production_spot_id
 * @property string $original_photo_path
 * @property string $resized_photo_path
 * @property bool $is_main,
 * @property int $creator_id
 * @property int|null $updater_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class RecyclerProductionSpotPhoto extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'is_main',
    ];

    /**
     * The attributes that should be hidden for arrays.
     */
    protected $hidden = [
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
            $model->creator_id = $user->id;
            $model->updated_at = null;
        });

        self::updating(function ($model) use ($user) {
            $model->updater_id = $user->id;
        });
    }

    public static function savePhotos($validated_data): void  //TODO resize photo
    {
        if (isset($validated_data['photos'])) {
            $path = 'private' . DIRECTORY_SEPARATOR .
                Auth::user()->company_id .
                RECYCLER_PRODUCTION_SPOTS_PATH .
                $validated_data['recycler_production_spot_id'] . DIRECTORY_SEPARATOR .
                'production_spot_photos' . DIRECTORY_SEPARATOR;

            foreach ($validated_data['photos'] as $photo) {
                $model = new self();
                $model->is_main = 0;
                $model->recycler_production_spot_id = $validated_data['recycler_production_spot_id'];
                $model->original_photo_path = $photo->store($path . 'original_photos');
                $model->resized_photo_path = $photo->store($path . 'resized_photos'); //TODO сжать файл
                $model->save();
            }
        }
    }

    public static function saveMainPhoto($validated_data): void
    {
        if (isset($validated_data['main_photo'])) {
            $old_main_photo = self::where(
                [
                    'recycler_production_spot_id' => $validated_data['recycler_production_spot_id'],
                    'is_main' => 1,
                ])->first();

            if ($old_main_photo) {
                $old_main_photo->is_main = 0;
                $old_main_photo->save();
            }

            $model = new self();

            $path = 'private' . DIRECTORY_SEPARATOR .
                Auth::user()->company_id .
                RECYCLER_PRODUCTION_SPOTS_PATH .
                $validated_data['recycler_production_spot_id'] . DIRECTORY_SEPARATOR .
                'production_spot_photos' . DIRECTORY_SEPARATOR;

            $model->is_main = 1;
            $model->recycler_production_spot_id = $validated_data['recycler_production_spot_id'];
            $model->original_photo_path = $validated_data['main_photo']->store($path . 'original_photos');
            $model->resized_photo_path = $validated_data['main_photo']->store($path . 'resized_photos'); //TODO сжать файл
            $model->save();
        }
    }

    /**
     * The RecyclerProductionSpotPhotos that belong to the RecyclerProductionSpot.
     */
    public function recyclerProductionSpotPhotos()
    {
        return $this->BelongsTo(RecyclerProductionSpot::class);
    }
}
