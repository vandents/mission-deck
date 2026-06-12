<?php

use yii\db\Migration;

class m260612_000003_create_asset_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%asset}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'type' => $this->string(20)->notNull()->defaultValue('other'),
            'latitude' => $this->decimal(10, 7)->null(),
            'longitude' => $this->decimal(10, 7)->null(),
            'description' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-asset-type', '{{%asset}}', 'type');
    }

    public function safeDown()
    {
        $this->dropTable('{{%asset}}');
    }
}
