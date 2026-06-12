<?php

declare(strict_types=1);

namespace app\models;

use Yii;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * @property int $id
 * @property string $username
 * @property string $email
 * @property string $password_hash
 * @property string $auth_key
 * @property int $created_at
 * @property int $updated_at
 */
class User extends ActiveRecord implements IdentityInterface
{
    public static function tableName(): string
    {
        return '{{%user}}';
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
            [['username', 'email'], 'required'],
            ['username', 'string', 'max' => 64],
            ['email', 'email'],
            [['username', 'email'], 'unique'],
        ];
    }

    public static function findIdentity($id): static|null
    {
        return static::findOne($id);
    }

    public static function findIdentityByAccessToken($token, $type = null): static|null
    {
        return null;
    }

    public static function findByUsername(string $username): static|null
    {
        return static::findOne(['username' => $username]);
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getAuthKey(): string|null
    {
        return $this->auth_key;
    }

    public function validateAuthKey($authKey): bool
    {
        return $this->auth_key === $authKey;
    }

    public function setPassword(string $password): void
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    public function generateAuthKey(): void
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }
}
