<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;

/**
 * Refactored PersonFactory aligned with modular patterns of lib/api/mock.ts.
 *
 * Key differences vs TypeScript implementation:
 * - Uses Laravel factory states to emulate generator config (name format, region, bio, pictures).
 * - Stores "city" as a single column; we include "City, Country" string for richer context.
 * - Coordinates default to Faker lat/long (backward compatible) unless city meta state is used.
 * - Randomness uses PHP's random_int / mt_rand instead of Web Crypto/Node crypto.
 *
 * Added functionality:
 * - Regional name pools with neutral name mixing; eastern/western/mononym formats.
 * - City metadata per region with timezone and coordinates.
 * - Region quotas helper mirroring DEFAULT_WEIGHTS and computeRegionCounts.
 * - Pictures generation using randomuser portrait indices for consistent variety.
 *
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * Mock generator configuration.
     * Mirrors patterns in lib/api/mock.ts with PHP idioms.
     *
     * @var array{
     *   name_format?: 'western'|'eastern'|'mononym',
     *   pictures_per_person?: int|null,
     *   include_bio?: bool,
     *   include_city_meta?: bool,
     * }
     */
    protected static array $config = [
        'name_format' => 'western',
        'pictures_per_person' => null, // default: random 1..6 to keep backward compatibility
        'include_bio' => true,
        'include_city_meta' => false,
    ];

    /**
     * Region quotas, aligned with TypeScript DEFAULT_WEIGHTS.
     *
     * @var array<string,float>
     */
    protected const DEFAULT_WEIGHTS = [
        'Asia' => 0.30,
        'Europe' => 0.25,
        'Africa' => 0.15,
        'Americas' => 0.20,
        'Oceania' => 0.10,
    ];

    /**
     * Minimal regional first-name pools and neutral names.
     * Intended for mock data variety comparable to TS.
     *
     * @var array<string,string[]>
     */
    protected const FIRST_NAMES_BY_REGION = [
        'Asia' => ['Yumi', 'Mei', 'Hana', 'Priya', 'Aiko', 'Sakura', 'Nari', 'Hina', 'Yuna', 'Akari'],
        'Europe' => ['Sofia', 'Emma', 'Mia', 'Emily', 'Lea', 'Anna', 'Lena', 'Clara', 'Ella', 'Nina'],
        'Africa' => ['Amina', 'Zainab', 'Fatima', 'Nia', 'Amara', 'Ada', 'Imani', 'Aisha', 'Zuri', 'Tia'],
        'Americas' => ['Maria', 'Sophia', 'Isabella', 'Olivia', 'Emma', 'Ava', 'Mia', 'Charlotte', 'Amelia', 'Harper'],
        'Oceania' => ['Isla', 'Evie', 'Ava', 'Aria', 'Zoe', 'Chloe', 'Mia', 'Ella', 'Ruby', 'Lily'],
    ];

    /**
     * Neutral cross-cultural names.
     * @var string[]
     */
    protected const NEUTRAL_FIRST_NAMES = ['Alice', 'Grace', 'Luna', 'Ava', 'Maya', 'Zoe', 'Chloe', 'Leah'];

    /**
     * Simplified last-name pool.
     * @var string[]
     */
    protected const LAST_NAMES = ['Smith', 'Garcia', 'Kim', 'Chen', 'Singh', 'Ivanov', 'Dubois', 'Nguyen', 'Hernandez', 'Brown'];

    /**
     * City metadata per region.
     * Note: Keep lists compact for maintainability; sufficient for mock variety.
     * @var array<string,array<int,array{city:string,country:string,lat:float,lon:float,timezone:string}>>
     */
    protected const CITIES_BY_REGION = [
        'Asia' => [
            ['city' => 'Tokyo', 'country' => 'Japan', 'lat' => 35.6762, 'lon' => 139.6503, 'timezone' => 'Asia/Tokyo'],
            ['city' => 'Seoul', 'country' => 'South Korea', 'lat' => 37.5665, 'lon' => 126.9780, 'timezone' => 'Asia/Seoul'],
            ['city' => 'Shanghai', 'country' => 'China', 'lat' => 31.2304, 'lon' => 121.4737, 'timezone' => 'Asia/Shanghai'],
            ['city' => 'Mumbai', 'country' => 'India', 'lat' => 19.0760, 'lon' => 72.8777, 'timezone' => 'Asia/Kolkata'],
            ['city' => 'Bangkok', 'country' => 'Thailand', 'lat' => 13.7563, 'lon' => 100.5018, 'timezone' => 'Asia/Bangkok'],
        ],
        'Europe' => [
            ['city' => 'London', 'country' => 'United Kingdom', 'lat' => 51.5074, 'lon' => -0.1278, 'timezone' => 'Europe/London'],
            ['city' => 'Berlin', 'country' => 'Germany', 'lat' => 52.5200, 'lon' => 13.4050, 'timezone' => 'Europe/Berlin'],
            ['city' => 'Paris', 'country' => 'France', 'lat' => 48.8566, 'lon' => 2.3522, 'timezone' => 'Europe/Paris'],
            ['city' => 'Madrid', 'country' => 'Spain', 'lat' => 40.4168, 'lon' => -3.7038, 'timezone' => 'Europe/Madrid'],
            ['city' => 'Rome', 'country' => 'Italy', 'lat' => 41.9028, 'lon' => 12.4964, 'timezone' => 'Europe/Rome'],
        ],
        'Africa' => [
            ['city' => 'Lagos', 'country' => 'Nigeria', 'lat' => 6.5244, 'lon' => 3.3792, 'timezone' => 'Africa/Lagos'],
            ['city' => 'Cairo', 'country' => 'Egypt', 'lat' => 30.0444, 'lon' => 31.2357, 'timezone' => 'Africa/Cairo'],
            ['city' => 'Nairobi', 'country' => 'Kenya', 'lat' => -1.2921, 'lon' => 36.8219, 'timezone' => 'Africa/Nairobi'],
            ['city' => 'Johannesburg', 'country' => 'South Africa', 'lat' => -26.2041, 'lon' => 28.0473, 'timezone' => 'Africa/Johannesburg'],
            ['city' => 'Accra', 'country' => 'Ghana', 'lat' => 5.6037, 'lon' => -0.1870, 'timezone' => 'Africa/Accra'],
        ],
        'Americas' => [
            ['city' => 'New York', 'country' => 'USA', 'lat' => 40.7128, 'lon' => -74.0060, 'timezone' => 'America/New_York'],
            ['city' => 'Mexico City', 'country' => 'Mexico', 'lat' => 19.4326, 'lon' => -99.1332, 'timezone' => 'America/Mexico_City'],
            ['city' => 'São Paulo', 'country' => 'Brazil', 'lat' => -23.5558, 'lon' => -46.6396, 'timezone' => 'America/Sao_Paulo'],
            ['city' => 'Toronto', 'country' => 'Canada', 'lat' => 43.6532, 'lon' => -79.3832, 'timezone' => 'America/Toronto'],
            ['city' => 'Buenos Aires', 'country' => 'Argentina', 'lat' => -34.6037, 'lon' => -58.3816, 'timezone' => 'America/Argentina/Buenos_Aires'],
        ],
        'Oceania' => [
            ['city' => 'Sydney', 'country' => 'Australia', 'lat' => -33.8688, 'lon' => 151.2093, 'timezone' => 'Australia/Sydney'],
            ['city' => 'Melbourne', 'country' => 'Australia', 'lat' => -37.8136, 'lon' => 144.9631, 'timezone' => 'Australia/Melbourne'],
            ['city' => 'Auckland', 'country' => 'New Zealand', 'lat' => -36.8485, 'lon' => 174.7633, 'timezone' => 'Pacific/Auckland'],
            ['city' => 'Brisbane', 'country' => 'Australia', 'lat' => -27.4698, 'lon' => 153.0251, 'timezone' => 'Australia/Brisbane'],
            ['city' => 'Perth', 'country' => 'Australia', 'lat' => -31.9523, 'lon' => 115.8613, 'timezone' => 'Australia/Perth'],
        ],
    ];

    /**
     * Update the mock generator configuration.
     *
     * @param array $update
     * @return void
     */
    public static function setGeneratorConfig(array $update): void
    {
        self::$config = array_merge(self::$config, $update);
    }

    /**
     * Build the default attributes for a Person model.
     * Backward compatible: random pictures count 1..6, random lat/long if city meta disabled.
     *
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $region = $this->pickRegion();
        $cityMeta = $this->pickCity($region);
        $name = $this->buildFullName($region, self::$config['name_format']);

        $picturesCount = self::$config['pictures_per_person'] ?? $this->faker->numberBetween(1, 6);
        $pictures = $this->makePictures($picturesCount, random_int(0, 5000));

        $includeBio = (bool) (self::$config['include_bio'] ?? true);
        $includeCityMeta = (bool) (self::$config['include_city_meta'] ?? false);

        return [
            'name' => $name,
            'age' => $this->randomAge(),
            'pictures' => $pictures,
            // When city meta is enabled, use structured coordinates; else fallback to faker (backward compatible)
            'latitude' => $includeCityMeta ? $cityMeta['lat'] : $this->faker->latitude(),
            'longitude' => $includeCityMeta ? $cityMeta['lon'] : $this->faker->longitude(),
            // Store "City, Country" for richer context; if not desired, callers can override via state.
            'city' => $cityMeta['city'] . ', ' . $cityMeta['country'],
            'bio' => $includeBio ? $this->faker->randomElement([
                'Coffee lover and weekend hiker',
                'Tech enthusiast and foodie',
                'Photographer exploring new places',
                'Bookworm who loves sci-fi',
                'Music junkie and vinyl collector',
                'Runner, traveler, and brunch aficionado',
                'Home cook experimenting with world cuisines',
                'Art gallery regular and film buff',
            ]) : null,
        ];
    }

    /**
     * State: set name format to western.
     */
    public function westernName(): self
    {
        return $this->state(fn () => [
            // setting config for this instance only via derived attribute
            'name' => $this->buildFullName($this->pickRegion(), 'western'),
        ]);
    }

    /**
     * State: set name format to eastern (family name first).
     */
    public function easternName(): self
    {
        return $this->state(fn () => [
            'name' => $this->buildFullName($this->pickRegion(), 'eastern'),
        ]);
    }

    /**
     * State: set name format to mononym (single name).
     */
    public function mononymName(): self
    {
        return $this->state(fn () => [
            'name' => $this->buildFullName($this->pickRegion(), 'mononym'),
        ]);
    }

    /**
     * State: enforce pictures count.
     *
     * @param int $count
     */
    public function picturesCount(int $count): self
    {
        $count = max(1, min(6, $count));
        return $this->state(fn () => [
            'pictures' => $this->makePictures($count, random_int(0, 5000)),
        ]);
    }

    /**
     * State: include or omit bio.
     */
    public function withBio(bool $include = true): self
    {
        return $this->state(fn () => [
            'bio' => $include ? $this->faker->randomElement([
                'Coffee lover and weekend hiker',
                'Tech enthusiast and foodie',
                'Photographer exploring new places',
                'Bookworm who loves sci-fi',
                'Music junkie and vinyl collector',
                'Runner, traveler, and brunch aficionado',
                'Home cook experimenting with world cuisines',
                'Art gallery regular and film buff',
            ]) : null,
        ]);
    }

    /**
     * State: set region and optionally bind city meta for coordinates.
     *
     * @param 'Asia'|'Europe'|'Africa'|'Americas'|'Oceania' $region
     * @param bool $useCityMeta
     */
    public function region(string $region, bool $useCityMeta = true): self
    {
        $cityMeta = $this->pickCity($region);
        return $this->state(fn () => [
            'city' => $cityMeta['city'] . ', ' . $cityMeta['country'],
            'latitude' => $useCityMeta ? $cityMeta['lat'] : $this->faker->latitude(),
            'longitude' => $useCityMeta ? $cityMeta['lon'] : $this->faker->longitude(),
        ]);
    }

    /**
     * State: ensure coordinates are from city metadata.
     */
    public function withCityMeta(): self
    {
        $region = $this->pickRegion();
        $cityMeta = $this->pickCity($region);
        return $this->state(fn () => [
            'city' => $cityMeta['city'] . ', ' . $cityMeta['country'],
            'latitude' => $cityMeta['lat'],
            'longitude' => $cityMeta['lon'],
        ]);
    }

    /**
     * Compute region counts given total and allowed list.
     * Mirrors TS computeRegionCounts.
     *
     * @param int $total
     * @param array<int,string>|null $allowed
     * @return array<string,int>
     */
    public static function computeRegionCounts(int $total, ?array $allowed = null): array
    {
        $regions = $allowed && count($allowed) > 0 ? $allowed : array_keys(self::DEFAULT_WEIGHTS);
        $weights = array_map(fn ($r) => self::DEFAULT_WEIGHTS[$r], $regions);
        $weightSum = array_sum($weights);
        $normalized = array_map(fn ($r) => self::DEFAULT_WEIGHTS[$r] / $weightSum, $regions);
        $counts = ['Asia' => 0, 'Europe' => 0, 'Africa' => 0, 'Americas' => 0, 'Oceania' => 0];
        $assigned = 0;
        foreach ($regions as $i => $r) {
            $c = (int) floor($normalized[$i] * $total);
            $counts[$r] = $c;
            $assigned += $c;
        }
        for ($i = 0; $i < $total - $assigned; $i++) {
            $r = Arr::random($regions);
            $counts[$r] += 1;
        }
        return $counts;
    }

    /**
     * Helper: choose a region using DEFAULT_WEIGHTS.
     * @return 'Asia'|'Europe'|'Africa'|'Americas'|'Oceania'
     */
    protected function pickRegion(): string
    {
        $regions = array_keys(self::DEFAULT_WEIGHTS);
        $weights = array_map(fn ($r) => self::DEFAULT_WEIGHTS[$r], $regions);
        $sum = array_sum($weights);
        $rand = mt_rand() / mt_getrandmax() * $sum;
        $acc = 0.0;
        foreach ($regions as $i => $r) {
            $acc += $weights[$i];
            if ($rand <= $acc) return $r;
        }
        return $regions[array_key_first(self::DEFAULT_WEIGHTS)];
    }

    /**
     * Helper: pick a city metadata from region.
     *
     * @param 'Asia'|'Europe'|'Africa'|'Americas'|'Oceania' $region
     * @return array{city:string,country:string,lat:float,lon:float,timezone:string}
     */
    protected function pickCity(string $region): array
    {
        $pool = self::CITIES_BY_REGION[$region] ?? self::CITIES_BY_REGION['Americas'];
        return Arr::random($pool);
    }

    /**
     * Helper: build full name based on format.
     *
     * @param 'Asia'|'Europe'|'Africa'|'Americas'|'Oceania' $region
     * @param 'western'|'eastern'|'mononym' $format
     */
    protected function buildFullName(string $region, string $format = 'western'): string
    {
        $regionalPool = self::FIRST_NAMES_BY_REGION[$region] ?? [];
        $basePool = (count($regionalPool) > 0) ? $regionalPool : array_merge(...array_values(self::FIRST_NAMES_BY_REGION));
        $useNeutral = random_int(0, 4) === 0; // 1 in 5 chance
        $first = $useNeutral ? Arr::random(self::NEUTRAL_FIRST_NAMES) : Arr::random($basePool);
        $last = Arr::random(self::LAST_NAMES);
        if ($format === 'eastern') return $last . ' ' . $first;
        if ($format === 'mononym') return $first;
        return $first . ' ' . $last;
    }

    /**
     * Helper: random age 18–60 (skew lightly towards 25–35).
     */
    protected function randomAge(): int
    {
        $base = 18 + random_int(0, 42); // 18..60
        return max(18, min(60, $base));
    }

    /**
     * Helper: make randomuser pictures.
     *
     * @param int $count
     * @param int $seed
     * @return string[]
     */
    protected function makePictures(int $count, int $seed): array
    {
        $pics = [];
        $idxBase = $seed % 100; // 0..99
        for ($i = 0; $i < $count; $i++) {
            $idx = ($idxBase + $i * 7) % 100;
            $pics[] = "https://randomuser.me/api/portraits/women/{$idx}.jpg";
        }
        return $pics;
    }
}