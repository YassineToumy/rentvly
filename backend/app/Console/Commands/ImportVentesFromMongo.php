<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Client as MongoClient;

class ImportVentesFromMongo extends Command
{
    protected $signature = 'import:ventes {--chunk=500 : Batch insert size}';
    protected $description = 'Import ventes from MongoDB (bienici.ventes_clean) to PostgreSQL';

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');

        $this->info('Connecting to MongoDB...');

        $mongo = new MongoClient(env('MONGO_IMPORT_DSN'));
        $collection = $mongo->bienici->ventes_clean;

        $total = $collection->countDocuments();
        $this->info("Found {$total} documents to import.");

        if ($total === 0) {
            $this->warn('Nothing to import.');
            return 0;
        }

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $batch = [];
        $imported = 0;
        $skipped = 0;

        $cursor = $collection->find([], ['noCursorTimeout' => true]);

        foreach ($cursor as $doc) {
            try {
                $row = $this->mapDocument($doc);
                $batch[] = $row;

                if (count($batch) >= $chunk) {
                    $this->insertBatch($batch);
                    $imported += count($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $skipped++;
                $this->newLine();
                $this->error("Skip {$doc['id']}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        // Remaining
        if (!empty($batch)) {
            $this->insertBatch($batch);
            $imported += count($batch);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Imported: {$imported} | Skipped: {$skipped}");

        return 0;
    }

    private function mapDocument($doc): array
    {
        $now = now();

        return [
            'external_id'        => $doc['id'] ?? null,
            'title'              => $doc['title'] ?? '',
            'description'        => $doc['description'] ?? null,

            'property_type'      => $doc['property_type'] ?? 'unknown',
            'is_new_property'    => (bool) ($doc['is_new_property'] ?? false),

            'price'              => (int) ($doc['price'] ?? 0),
            'price_per_sqm'      => $doc['price_per_sqm'] ?? null,
            'price_has_decreased'=> (bool) ($doc['price_has_decreased'] ?? false),
            'reduced_vat'        => (bool) ($doc['reduced_vat'] ?? false),

            'surface_area'       => $doc['surface_area'] ?? null,
            'rooms_quantity'     => isset($doc['rooms_quantity']) ? (int) $doc['rooms_quantity'] : null,
            'surface_per_room'   => $doc['surface_per_room'] ?? null,

            'interior_features'  => json_encode([
                'is_disabled_friendly' => (bool) ($doc['is_disabled_friendly'] ?? false),
                'has_elevator'         => (bool) ($doc['has_elevator'] ?? false),
            ]),

            'exterior_features'  => json_encode([
                'has_garden'  => (bool) ($doc['has_garden'] ?? false),
                'has_terrace' => (bool) ($doc['has_terrace'] ?? false),
                'has_balcony' => (bool) ($doc['has_balcony'] ?? false),
                'has_pool'    => (bool) ($doc['has_pool'] ?? false),
            ]),

            'other_features'     => json_encode([
                'has_parking'          => (bool) ($doc['has_parking'] ?? false),
                'has_cellar'           => (bool) ($doc['has_cellar'] ?? false),
                'has_air_conditioning' => (bool) ($doc['has_air_conditioning'] ?? false),
                'has_fireplace'        => (bool) ($doc['has_fireplace'] ?? false),
            ]),

            'equipment_score'    => (int) ($doc['equipment_score'] ?? 0),

            'city'               => $doc['city'] ?? '',
            'postal_code'        => $doc['postal_code'] ?? '00000',
            'department_code'    => $doc['department_code'] ?? substr((string) ($doc['postal_code'] ?? '00'), 0, 2),
            'district_name'      => $doc['district_name'] ?? null,
            'code_insee'         => $doc['code_insee'] ?? null,
            'latitude'           => $doc['latitude'] ?? null,
            'longitude'          => $doc['longitude'] ?? null,

            'owner_type'         => $doc['owner_type'] ?? null,
            'owner_name'         => $doc['owner_name'] ?? null,
            'is_pro'             => (bool) ($doc['is_pro'] ?? false),

            'photos'             => json_encode($this->toArray($doc['photos'] ?? [])),
            'photos_count'       => (int) ($doc['photos_count'] ?? 0),

            'publication_date'   => $this->toTimestamp($doc['publication_date'] ?? null),
            'modification_date'  => $this->toTimestamp($doc['modification_date'] ?? null),
            'delivery_date'      => $this->toDate($doc['delivery_date'] ?? null),
            'scraped_at'         => $this->toTimestamp($doc['scraped_at'] ?? null),
            'cleaned_at'         => $this->toTimestamp($doc['cleaned_at'] ?? null),

            'created_at'         => $now,
            'updated_at'         => $now,
        ];
    }

    private function insertBatch(array $rows): void
    {
        DB::table('ventes')->upsert($rows, ['external_id'], array_keys($rows[0]));
    }

    private function toTimestamp($value): ?string
    {
        if ($value === null) return null;

        // MongoDB UTCDateTime
        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d H:i:s');
        }

        // String date
        if (is_string($value)) {
            try { return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s'); }
            catch (\Throwable) { return null; }
        }

        return null;
    }

    private function toDate($value): ?string
    {
        if ($value === null) return null;

        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d');
        }

        if (is_string($value)) {
            try { return \Carbon\Carbon::parse($value)->format('Y-m-d'); }
            catch (\Throwable) { return null; }
        }

        return null;
    }

    private function toArray($value): array
    {
        if ($value instanceof \MongoDB\Model\BSONArray) {
            return $value->getArrayCopy();
        }
        if (is_array($value)) return $value;
        return [];
    }
}