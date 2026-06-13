<?php

use yii\db\Migration;

class m260613_000003_create_telemetry_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%telemetry}}', [
            'id' => $this->bigPrimaryKey(),
            'drone_id' => $this->integer()->notNull(),
            'mission_id' => $this->integer()->null(),
            'latitude' => $this->decimal(10, 7)->notNull(),
            'longitude' => $this->decimal(10, 7)->notNull(),
            'altitude' => $this->decimal(6, 2)->notNull(),
            'heading' => $this->decimal(5, 2)->null(),     // degrees, 0-360
            'battery_pct' => $this->integer()->null(),     // 0-100
            'status' => $this->string(20)->null(),
            'recorded_at' => $this->integer()->notNull(),
        ]);

        // The live view asks for "latest ping per drone", so index that lookup.
        $this->createIndex('idx-telemetry-drone-time', '{{%telemetry}}', ['drone_id', 'recorded_at']);

        $this->addForeignKey('fk-telemetry-drone', '{{%telemetry}}', 'drone_id', '{{%drone}}', 'id', 'CASCADE');
        $this->addForeignKey('fk-telemetry-mission', '{{%telemetry}}', 'mission_id', '{{%mission}}', 'id', 'SET NULL');
    }

    public function safeDown()
    {
        $this->dropTable('{{%telemetry}}');
    }
}
