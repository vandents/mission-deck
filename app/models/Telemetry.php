<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * A single telemetry ping reported by a drone in flight.
 *
 * @property int $id
 * @property int $drone_id
 * @property int|null $mission_id
 * @property float $latitude
 * @property float $longitude
 * @property float $altitude meters above takeoff (AGL)
 * @property float|null $heading degrees, 0-360
 * @property int|null $battery_pct 0-100
 * @property string|null $status
 * @property int $recorded_at unix timestamp
 *
 * @property-read Drone $drone
 * @property-read Mission|null $mission
 */
class Telemetry extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%telemetry}}';
    }

    public function rules(): array
    {
        return [
            [['drone_id', 'latitude', 'longitude', 'altitude'], 'required'],
            [['drone_id', 'mission_id', 'battery_pct', 'recorded_at'], 'integer'],
            [['latitude', 'longitude', 'altitude', 'heading'], 'number'],
            ['latitude', 'number', 'min' => -90, 'max' => 90],
            ['longitude', 'number', 'min' => -180, 'max' => 180],
            ['battery_pct', 'integer', 'min' => 0, 'max' => 100],
            ['status', 'string', 'max' => 20],
            ['drone_id', 'exist', 'targetClass' => Drone::class, 'targetAttribute' => 'id'],
            ['mission_id', 'exist', 'targetClass' => Mission::class, 'targetAttribute' => 'id', 'skipOnEmpty' => true],
        ];
    }

    public function getDrone(): ActiveQuery
    {
        return $this->hasOne(Drone::class, ['id' => 'drone_id']);
    }

    public function getMission(): ActiveQuery
    {
        return $this->hasOne(Mission::class, ['id' => 'mission_id']);
    }
}
