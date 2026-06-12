<?php

declare(strict_types=1);

namespace app\commands;

use app\models\User;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Manages application users (there is no public signup).
 */
class UserController extends Controller
{
    /**
     * Creates a user: php yii user/create <username> <email> <password>
     */
    public function actionCreate(string $username, string $email, string $password): int
    {
        $user = new User(['username' => $username, 'email' => $email]);
        $user->setPassword($password);
        $user->generateAuthKey();

        if ($user->save()) {
            $this->stdout("User '{$username}' created.\n");
            return ExitCode::OK;
        }

        $this->stderr(print_r($user->errors, true));
        return ExitCode::UNSPECIFIED_ERROR;
    }

    /**
     * Resets a user's password: php yii user/set-password <username> <password>
     */
    public function actionSetPassword(string $username, string $password): int
    {
        $user = User::findByUsername($username);

        if (!$user) {
            $this->stderr("User '{$username}' not found.\n");
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $user->setPassword($password);

        if ($user->save(false)) {
            $this->stdout("Password updated for '{$username}'.\n");
            return ExitCode::OK;
        }

        return ExitCode::UNSPECIFIED_ERROR;
    }
}
