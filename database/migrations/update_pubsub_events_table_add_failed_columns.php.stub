<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

final class UpdatePubsubEventsTableAddFailedColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function up(): void
    {
        $tableNames = $this->getTableNames();

        Schema::table($tableNames['events'], function (Blueprint $table) {
            $table->timestamp('failed_at')->nullable();
            $table->longText('exception')->nullable();

            $table->index(['type', 'failed_at'], 'events_failed_at_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     * @throws Exception
     */
    public function down(): void
    {
        $tableNames = $this->getTableNames();

        Schema::table($tableNames['events'], function (Blueprint $table) {
            $table->dropColumn(['failed_at', 'exception']);
        });
    }

    /**
     * @return array<string, string>
     *
     * @throws Exception
     */
    private function getTableNames(): array
    {
        /** @psalm-var array<string, string> $tableNames */
        $tableNames = config('pubsub.tables');

        if (empty($tableNames)) {
            throw new \RuntimeException('Error: config/pubsub.php not found and defaults could not be merged. Please publish the package configuration before proceeding, or drop the tables manually.');
        }

        return $tableNames;
    }
}
