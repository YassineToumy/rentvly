<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MongoDB\Client as MongoClient;

class ImportVentesFromMongo extends Command
{
    protected $signature = 'import:ventes {--chunk=100 : Batch insert size}';
    protected $description = 'Import ventes from MongoDB ventes_clean into PostgreSQL';

    /** @var array<string, bool> */
    private array $validDepartments = [];

    /** @var array<string, string> */
    private array $departmentRegions = [];

    /** @var array<string, bool> */
    private array $validCommunes = [];

    public function handle(): int
    {
        $chunk = (int) $this->option('chunk');

        ini_set('memory_limit', '512M');
        DB::disableQueryLog();

        $mongoDb = env('MONGO_IMPORT_DATABASE', 'bienici');
        $mongoCollection = env('MONGO_IMPORT_VENTES_COLLECTION', 'ventes_clean');
        $dsn = env('MONGO_IMPORT_DSN', env('MONGO_URI', 'mongodb://127.0.0.1:27017'));

        $this->info('Connecting to MongoDB...');

        $mongo = new MongoClient($dsn);
        $collection = $mongo->selectDatabase($mongoDb)->selectCollection($mongoCollection);

        $this->info("Source: {$mongoDb}.{$mongoCollection}");

        $total = $collection->countDocuments();
        $this->info("Found {$total} documents to import.");

        if ($total === 0) {
            $this->warn('Nothing to import. Run python cleaner_ventes.py first.');
            return 0;
        }

        $this->validDepartments = array_fill_keys(
            DB::table('departements')->pluck('code_departement')->all(),
            true
        );
        $this->departmentRegions = DB::table('departements')
            ->pluck('code_region', 'code_departement')
            ->all();
        $this->validCommunes = array_fill_keys(
            DB::table('communes')->pluck('code_commune')->all(),
            true
        );

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $batch = [];
        $imported = 0;
        $skipped = 0;

        $cursor = $collection->find([], [
            'batchSize' => $chunk,
            'typeMap' => [
                'root' => 'array',
                'document' => 'array',
                'array' => 'array',
            ],
        ]);

        foreach ($cursor as $doc) {
            try {
                $row = $this->mapDocument($doc);
                if ($row === null) {
                    $skipped++;
                } else {
                    $batch[] = $row;
                }

                if (count($batch) >= $chunk) {
                    $this->insertBatch($batch);
                    $imported += count($batch);
                    $batch = [];
                }
            } catch (\Throwable $e) {
                $skipped++;
                $this->newLine();
                $id = $doc['id'] ?? '?';
                $this->error("Skip {$id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        if (!empty($batch)) {
            $this->insertBatch($batch);
            $imported += count($batch);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Done! Imported: {$imported} | Skipped: {$skipped}");
        $this->info('Listings page: /listings (API GET /api/v1/ventes)');

        return 0;
    }

    private function mapDocument(array $doc): ?array
    {
        $externalId = $this->stringVal($doc['id'] ?? null);
        if ($externalId === null || $externalId === '') {
            return null;
        }

        $postal = $this->postalCode($doc['postal_code'] ?? null);
        $department = $this->departmentCode($doc['department_code'] ?? null, $postal);
        if ($department === null) {
            return null;
        }

        $photos = $this->photoUrls($doc['photos'] ?? []);
        $now = now();

        return [
            'external_id'         => $externalId,
            'title'               => mb_substr($this->stringVal($doc['title'] ?? '') ?? '', 0, 255),
            'description'         => $this->stringVal($doc['description'] ?? null),

            'property_type'       => $this->stringVal($doc['property_type'] ?? 'flat') ?: 'flat',
            'is_new_property'     => (bool) ($doc['is_new_property'] ?? false),

            'price'               => (int) ($doc['price'] ?? 0),
            'price_per_sqm'       => $this->floatVal($doc['price_per_sqm'] ?? null),
            'price_has_decreased' => (bool) ($doc['price_has_decreased'] ?? false),
            'reduced_vat'         => (bool) ($doc['reduced_vat'] ?? false),

            'surface_area'        => $this->floatVal($doc['surface_area'] ?? null),
            'rooms_quantity'      => isset($doc['rooms_quantity']) ? (int) $doc['rooms_quantity'] : null,
            'surface_per_room'    => $this->floatVal($doc['surface_per_room'] ?? null),

            'interior_features'   => json_encode([
                'is_disabled_friendly' => (bool) ($doc['is_disabled_friendly'] ?? false),
                'has_elevator'         => (bool) ($doc['has_elevator'] ?? false),
            ]),

            'exterior_features'   => json_encode([
                'has_garden'  => (bool) ($doc['has_garden'] ?? false),
                'has_terrace' => (bool) ($doc['has_terrace'] ?? false),
                'has_balcony' => (bool) ($doc['has_balcony'] ?? false),
                'has_pool'    => (bool) ($doc['has_pool'] ?? false),
            ]),

            'other_features'      => json_encode([
                'has_parking'          => (bool) ($doc['has_parking'] ?? false),
                'has_cellar'           => (bool) ($doc['has_cellar'] ?? false),
                'has_air_conditioning' => (bool) ($doc['has_air_conditioning'] ?? false),
                'has_fireplace'        => (bool) ($doc['has_fireplace'] ?? false),
            ]),

            'equipment_score'     => (int) ($doc['equipment_score'] ?? 0),

            'city'                => mb_substr($this->stringVal($doc['city'] ?? '') ?? '', 0, 255),
            'postal_code'         => $postal ?: '00000',
            'department_code'     => $department,
            'code_region'         => $this->departmentRegions[$department] ?? null,
            'district_name'       => $this->stringVal($doc['district_name'] ?? null),
            'code_insee'          => $this->insee($doc['code_insee'] ?? null),
            'code_commune'        => $this->communeCode($doc['code_insee'] ?? null),
            'latitude'            => $this->floatVal($doc['latitude'] ?? null),
            'longitude'           => $this->floatVal($doc['longitude'] ?? null),

            'owner_type'          => mb_substr((string) ($this->stringVal($doc['owner_type'] ?? null) ?? ''), 0, 255) ?: null,
            'owner_name'          => mb_substr((string) ($this->stringVal($doc['owner_name'] ?? null) ?? ''), 0, 255) ?: null,
            'is_pro'              => (bool) ($doc['is_pro'] ?? false),

            'photos'              => json_encode($photos),
            'photos_count'        => (int) ($doc['photos_count'] ?? count($photos)),

            'publication_date'    => $this->toTimestamp($doc['publication_date'] ?? null),
            'modification_date'   => $this->toTimestamp($doc['modification_date'] ?? null),
            'delivery_date'       => $this->toDate($doc['delivery_date'] ?? null),
            'scraped_at'          => $this->toTimestamp($doc['scraped_at'] ?? null),
            'cleaned_at'          => $this->toTimestamp($doc['cleaned_at'] ?? null),

            'created_at'          => $now,
            'updated_at'          => $now,
        ];
    }

    private function insertBatch(array $rows): void
    {
        $update = array_keys($rows[0]);
        $update = array_values(array_filter($update, fn ($col) => !in_array($col, ['external_id', 'created_at'], true)));

        DB::table('ventes')->upsert($rows, ['external_id'], $update);
    }

    private function photoUrls(mixed $photos): array
    {
        if (!is_array($photos)) {
            return [];
        }

        $urls = [];
        foreach ($photos as $photo) {
            if (is_string($photo) && $photo !== '') {
                $urls[] = $photo;
                continue;
            }
            if (!is_array($photo)) {
                continue;
            }
            $url = $photo['url'] ?? $photo['url_photo'] ?? $photo['src'] ?? null;
            if (is_string($url) && $url !== '') {
                $urls[] = $url;
            }
        }

        return array_slice(array_values(array_unique($urls)), 0, 10);
    }

    private function postalCode(mixed $value): string
    {
        $raw = preg_replace('/\D/', '', (string) $this->stringVal($value));
        if ($raw === '') {
            return '00000';
        }

        return substr(str_pad($raw, 5, '0'), 0, 5);
    }

    private function departmentCode(mixed $value, string $postal): ?string
    {
        $candidates = [];

        $given = strtoupper(trim((string) ($this->stringVal($value) ?? '')));
        if ($given !== '') {
            $candidates[] = $given;
        }

        if (str_starts_with($postal, '97') || str_starts_with($postal, '98')) {
            $candidates[] = substr($postal, 0, 3);
        } else {
            $two = substr($postal, 0, 2);
            $candidates[] = $two;
            if ($two === '20') {
                $n = (int) $postal;
                $candidates[] = ($n >= 20200) ? '2B' : '2A';
            }
        }

        foreach ($candidates as $code) {
            if (isset($this->validDepartments[$code])) {
                return $code;
            }
        }

        return null;
    }

    private function communeCode(mixed $insee): ?string
    {
        $code = $this->insee($insee);
        if ($code === null || !isset($this->validCommunes[$code])) {
            return null;
        }

        return $code;
    }

    private function insee(mixed $value): ?string
    {
        $raw = preg_replace('/\s+/', '', (string) ($this->stringVal($value) ?? ''));
        if ($raw === '') {
            return null;
        }

        return substr($raw, 0, 5);
    }

    private function stringVal(mixed $value): ?string
    {
        if ($value === null || is_array($value)) {
            return null;
        }
        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('c');
        }

        return trim((string) $value);
    }

    private function floatVal(mixed $value): ?float
    {
        if ($value === null || $value === '' || is_array($value) || is_bool($value)) {
            return null;
        }

        return (float) $value;
    }

    private function toTimestamp(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d H:i:s');
        }

        if (is_string($value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }

    private function toDate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \MongoDB\BSON\UTCDateTime) {
            return $value->toDateTime()->format('Y-m-d');
        }

        if (is_string($value)) {
            try {
                return \Carbon\Carbon::parse($value)->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
