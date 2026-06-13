<?php

use yii\db\Migration;

class m260613_000002_create_waypoint_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%waypoint}}', [
            'id' => $this->primaryKey(),
            'mission_id' => $this->integer()->notNull(),
            'seq' => $this->integer()->notNull(),
            'latitude' => $this->decimal(10, 7)->notNull(),
            'longitude' => $this->decimal(10, 7)->notNull(),
            'altitude' => $this->decimal(6, 2)->notNull()->defaultValue(50), // meters above takeoff (AGL)
            'speed' => $this->decimal(5, 2)->null(),                          // m/s
        ]);

        // Waypoints are always read in mission order, so index the pair.
        $this->createIndex('idx-waypoint-mission-seq', '{{%waypoint}}', ['mission_id', 'seq']);

        // Deleting a mission removes its waypoints.
        $this->addForeignKey('fk-waypoint-mission', '{{%waypoint}}', 'mission_id', '{{%mission}}', 'id', 'CASCADE');
    }

    public function safeDown()
    {
        $this->dropTable('{{%waypoint}}');
    }
}
