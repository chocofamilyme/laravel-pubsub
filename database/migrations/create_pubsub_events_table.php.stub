<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePubsubEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableNames = $this->getTableNames();

        Schema::create($tableNames['events'], function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type', 3);
            $table->string('name');
            $table->jsonb('payload');
            $table->jsonb('headers')->nullable();
            $table->string('exchange', 10)->nullable();
            $table->string('exchange_type', 20)->nullable();
            $table->string('routing_key', 50);
            $table->timestamp('created_at');
            $table->timestamp('processed_at')->nullable();

            $table->index(['type', 'processed_at'], 'events_processed_at_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableNames = $this->getTableNames();

        Schema::drop($tableNames['events']);
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
