<?php

namespace App\Jobs;

use App\Exports\EloquentExporter;
use App\Models\Exporter\Export;
use App\Models\NotificationType;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DatabaseToCsvExporter implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
    * @var int
    */
    public $timeout = 1800;

    /**
    * @var Export
    */
    private $export;

    /**
    * @var EloquentExporter
    */
    private $exporter;

    /**
    * Create a new job instance.
    *
    * @param Export $export
    */
    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    /**
    * @return EloquentExporter
    */
    public function getExporter()
    {
        if (empty($this->exporter)) {
            $this->exporter = new EloquentExporter($this->export);
        }

        return $this->exporter;
    }

    /**
    * @param Export $export
    *
    * @return string
    */
    public function transformTenantFilename(Export $export)
    {
        return sprintf(
            '%s/csv/%s/%s',
            $export->getConnectionName(),
            $export->hash,
            $export->filename
        );
    }

    /**
    * @param string $filename
    *
    * @return string
    */
    public function transformTenantUrl($filename)
    {
        return Storage::url($filename);
    }

    /**
    * @param EloquentExporter $exporter
    *
    * @return string
    */
    public function getMessageToNotification(EloquentExporter $exporter)
    {
        return "Foram exportados {$exporter->getExportCount()} registros. Clique aqui para fazer download do arquivo {$this->export->filename}.";
    }

    /**
    * Execute the job.
    *
    * @param NotificationService $notification
    * @param DatabaseManager     $manager
    *
    * @throws FileNotFoundException
    *
    * @return void
    */
    public function handle(NotificationService $notification, DatabaseManager $manager)
    {
        $exporter = $this->getExporter();

        $file = $this->export->hash;

        $reportData = DB::select($exporter->query());

        $csvColumns = [];

        if($reportData) {
            foreach ($reportData[0] as $key => $value) {
                array_push($csvColumns,$key);
            }

            $fileCsv = fopen(Storage::path($file), 'w');

            fputcsv($fileCsv, $csvColumns);

            foreach ($reportData as $data) {
                $csvRow = [];

                foreach ($data as $key => $value) {
                    array_push($csvRow,$value);
                }

                fputcsv($fileCsv, $csvRow);
            }

            fclose($fileCsv);

            if (Storage::exists($file)) {
                Storage::put(
                    $filename = $this->transformTenantFilename($this->export),
                    Storage::get($file)
                );
                Storage::delete($file);
                $url = $this->transformTenantUrl($filename);

                $notification->createByUser(
                    $this->export->user_id,
                    $this->getMessageToNotification($exporter),
                    $url,
                    NotificationType::EXPORT_STUDENT
                );
            }

            $this->export->url = $url;
            $this->export->save();
        }
    }

    public function tags()
    {
        return [
            $this->export->getConnectionName(),
            'csv-export'
        ];
    }
}