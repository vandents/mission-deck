<?php

use yii\db\Migration;

/**
 * Seeds a starter fleet so the app (and the Live Ops map) has drones out of the box.
 */
class m260613_000005_seed_drones extends Migration
{
    private const CALLSIGNS = ['Alpha', 'Bravo', 'Charlie', 'Delta', 'Echo', 'Foxtrot', 'Golf', 'Hotel'];

    public function safeUp()
    {
        $now = time();
        $rows = [];
        foreach (self::CALLSIGNS as $i => $callsign) {
            $rows[] = [
                'Sentaero 6 ' . $callsign,
                'Sentaero 6',
                sprintf('SN-10%02d', $i + 1),
                'available',
                $now,
                $now,
            ];
        }

        $this->batchInsert('{{%drone}}', ['name', 'model', 'serial_number', 'status', 'created_at', 'updated_at'], $rows);
    }

    public function safeDown()
    {
        $serials = array_map(static fn (int $i) => sprintf('SN-10%02d', $i + 1), array_keys(self::CALLSIGNS));
        $this->delete('{{%drone}}', ['serial_number' => $serials]);
    }
}
