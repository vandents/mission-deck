<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * An inspectable piece of infrastructure that missions are flown against.
 *
 * @property int $id
 * @property string $name
 * @property string $type
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $description
 * @property int $created_at
 * @property int $updated_at
 */
class Asset extends ActiveRecord
{
    public const TYPE_TOWER = 'tower';
    public const TYPE_PIPELINE = 'pipeline';
    public const TYPE_POWER_LINE = 'power_line';
    public const TYPE_BRIDGE = 'bridge';
    public const TYPE_SOLAR_FARM = 'solar_farm';
    public const TYPE_OTHER = 'other';

    public static function tableName(): string
    {
        return '{{%asset}}';
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
            ['type', 'in', 'range' => array_keys(self::typeOptions())],
            ['latitude', 'number', 'min' => -90, 'max' => 90],
            ['longitude', 'number', 'min' => -180, 'max' => 180],
            ['description', 'string'],
        ];
    }

    /** @return array<string, string> type value => display label */
    public static function typeOptions(): array
    {
        return [
            self::TYPE_TOWER => 'Tower',
            self::TYPE_PIPELINE => 'Pipeline',
            self::TYPE_POWER_LINE => 'Power Line',
            self::TYPE_BRIDGE => 'Bridge',
            self::TYPE_SOLAR_FARM => 'Solar Farm',
            self::TYPE_OTHER => 'Other',
        ];
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? $this->type;
    }
}
