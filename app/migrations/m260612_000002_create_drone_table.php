<?php

use yii\db\Migration;

class m260612_000002_create_drone_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%drone}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'model' => $this->string()->null(),
            'serial_number' => $this->string(64)->notNull()->unique(),
            'status' => $this->string(20)->notNull()->defaultValue('available'),
            'notes' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-drone-status', '{{%drone}}', 'status');
    }

    public function safeDown()
    {
        $this->dropTable('{{%drone}}');
    }
}
