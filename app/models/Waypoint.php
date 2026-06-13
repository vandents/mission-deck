<?php

declare(strict_types=1);

namespace app\models;

use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * One point in a mission's flight path. Ordered within a mission by `seq`.
 *
 * @property int $id
 * @property int $mission_id
 * @property int $seq
 * @property float $latitude
 * @property float $longitude
 * @property float $altitude meters above takeoff (AGL)
 * @property float|null $speed m/s
 *
 * @property-read Mission $mission
 */
class Waypoint extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%waypoint}}';
    }

    public function rules(): array
    {
        return [
            [['mission_id', 'seq', 'latitude', 'longitude'], 'required'],
            [['mission_id', 'seq'], 'integer'],
            ['latitude', 'number', 'min' => -90, 'max' => 90],
            ['longitude', 'number', 'min' => -180, 'max' => 180],
            ['altitude', 'number', 'min' => 0],
            ['speed', 'number', 'min' => 0],
        ];
    }

    public function getMission(): ActiveQuery
    {
        return $this->hasOne(Mission::class, ['id' => 'mission_id']);
    }
}
