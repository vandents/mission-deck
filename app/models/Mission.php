<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * A planned flight: an ordered set of waypoints, optionally flown by a drone
 * against an inspectable asset.
 *
 * @property int $id
 * @property string $name
 * @property int|null $drone_id
 * @property int|null $asset_id
 * @property string $status
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read Drone|null $drone
 * @property-read Asset|null $asset
 * @property-read Waypoint[] $waypoints
 */
class Mission extends ActiveRecord
{
    public const STATUS_PLANNED = 'planned';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABORTED = 'aborted';

    public static function tableName(): string
    {
        return '{{%mission}}';
    }

    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    public function rules(): array
    {
        return [
            ['name', 'required'],
            ['name', 'string', 'max' => 255],
            [['drone_id', 'asset_id'], 'integer'],
            ['drone_id', 'exist', 'targetClass' => Drone::class, 'targetAttribute' => 'id'],
            ['asset_id', 'exist', 'targetClass' => Asset::class, 'targetAttribute' => 'id'],
            ['status', 'in', 'range' => array_keys(self::statusOptions())],
            ['notes', 'string'],
        ];
    }

    public function getDrone(): ActiveQuery
    {
        return $this->hasOne(Drone::class, ['id' => 'drone_id']);
    }

    public function getAsset(): ActiveQuery
    {
        return $this->hasOne(Asset::class, ['id' => 'asset_id']);
    }

    public function getWaypoints(): ActiveQuery
    {
        return $this->hasMany(Waypoint::class, ['mission_id' => 'id'])->orderBy('seq');
    }

    /** @return array<string, string> status value => display label */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PLANNED => 'Planned',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_ABORTED => 'Aborted',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }
}
