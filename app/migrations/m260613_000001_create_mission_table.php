<?php

use yii\db\Migration;

class m260613_000001_create_mission_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%mission}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'drone_id' => $this->integer()->null(),
            'asset_id' => $this->integer()->null(),
            'status' => $this->string(20)->notNull()->defaultValue('planned'),
            'notes' => $this->text()->null(),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $this->createIndex('idx-mission-status', '{{%mission}}', 'status');

        // Deleting a drone or asset leaves its missions intact but unassigned.
        $this->addForeignKey('fk-mission-drone', '{{%mission}}', 'drone_id', '{{%drone}}', 'id', 'SET NULL');
        $this->addForeignKey('fk-mission-asset', '{{%mission}}', 'asset_id', '{{%asset}}', 'id', 'SET NULL');
    }

    public function safeDown()
    {
        $this->dropTable('{{%mission}}');
    }
}
