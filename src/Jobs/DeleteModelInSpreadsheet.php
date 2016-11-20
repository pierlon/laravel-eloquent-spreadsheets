<?php

namespace Rhinodontypicus\EloquentSpreadsheets\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Rhinodontypicus\EloquentSpreadsheets\Jobs\Traits\Saveable;
use Rhinodontypicus\EloquentSpreadsheets\SpreadsheetService;

class DeleteModelInSpreadsheet implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels, Saveable;

    /**
     * @var
     */
    private $modelId;

    /**
     * @var
     */
    private $config;

    /**
     * Create a new job instance.
     * @param $modelId
     * @param $config
     */
    public function __construct($modelId, $config)
    {
        $this->modelId = $modelId;
        $this->config = $config;
        $this->prepareConfig();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $rowToInsert = $this->getRowToInsert();

        if ($rowToInsert === false) {
            return;
        }

        $this->insertModelToSheet($rowToInsert);
    }

    /**
     * @return array
     */
    public function getModelSyncedData()
    {
        $result = [];

        foreach (range(0, count(range($this->startColumn, $this->endColumn)) - 1) as $key) {
            $result[$key] = '';
        }

        ksort($result);

        return $result;
    }

    /**
     * @return bool|int|string
     */
    private function getRowToInsert()
    {
        $idColumn = $this->config['sync_attributes']['id'];
        $range = "{$this->config['list_name']}!{$idColumn}2:$idColumn";
        $response = app(SpreadsheetService::class)->service()->spreadsheets_values->get(
            $this->config['spreadsheet_id'],
            $range
        );

        $values = $response->getValues();

        if (empty($values)) {
            return false;
        }

        foreach ($values as $index => $value) {
            if (empty($value[0]) || $value[0] != $this->modelId) {
                continue;
            }

            return $index + 2;
        }

        return false;
    }
}
