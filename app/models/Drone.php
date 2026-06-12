<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string|null $model
 * @property string $serial_number
 * @property string $status
 * @property string|null $notes
 * @property int $created_at
 * @property int $updated_at
 */
class Drone extends ActiveRecord
{
    public const STATUS_AVAILABLE = 'available';
    public const STATUS_IN_MISSION = 'in_mission';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_RETIRED = 'retired';

    public static function tableName(): string
    {
        return '{{%drone}}';
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
            [['name', 'serial_number'], 'required'],
            [['name', 'model'], 'string', 'max' => 255],
            ['serial_number', 'string', 'max' => 64],
            ['serial_number', 'unique'],
            ['status', 'in', 'range' => array_keys(self::statusOptions())],
            ['notes', 'string'],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'serial_number' => 'Serial Number',
        ];
    }

    /** @return array<string, string> status value => display label */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_AVAILABLE => 'Available',
            self::STATUS_IN_MISSION => 'In Mission',
            self::STATUS_MAINTENANCE => 'Maintenance',
            self::STATUS_RETIRED => 'Retired',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }
}
