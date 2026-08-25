<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportFromPrestaShop extends Command
{
    protected $signature = 'import:prestashop';

    protected $description = 'Import data from a live PrestaShop database into the Laravel application database';

    /** @var \Illuminate\Database\ConnectionInterface */
    private $ps;

    public function handle(): int
    {
        $this->ps = DB::connection('prestashop');

        $this->info('Starting PrestaShop → Laravel import…');
        $this->newLine();

        $this->truncateTables();
        $this->importTaxRates();
        $this->importCategories();
        $this->importProducts();
        $this->importCategoryProductPivot();
        $this->importProductImages();
        $this->importStock();
        $this->importCustomers();
        $this->importAddresses();
        $this->importShippingMethods();

        $this->newLine();
        $this->info('Import complete.');

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Truncate
    // -------------------------------------------------------------------------

    private function truncateTables(): void
    {
        $this->info('Truncating target tables…');

        $tables = [
            'cart_items',
            'carts',
            'order_items',
            'orders',
            'addresses',
            'customers',
            'stock_availabilities',
            'product_images',
            'category_product',
            'products',
            'categories',
            'shipping_methods',
            'tax_rates',
        ];

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            DB::table($table)->delete();
            // Reset auto-increment so IDs are consistent on re-runs.
            DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
            $this->line("  Cleared <comment>{$table}</comment>");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Tax rates
    // -------------------------------------------------------------------------

    private function importTaxRates(): void
    {
        $this->info('Importing tax rates…');

        $rows = $this->ps
            ->table('ps_tax as t')
            ->join('ps_tax_lang as tl', function ($join) {
                $join->on('tl.id_tax', '=', 't.id_tax')
                     ->where('tl.id_lang', '=', 1);
            })
            ->select('tl.name', 't.rate', 't.active')
            ->get();

        $now = now();
        $records = $rows->map(fn ($r) => [
            'name'       => (string) $r->name,
            'rate'       => (float) $r->rate,
            'active'     => (bool) $r->active,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        if ($records) {
            DB::table('tax_rates')->insert($records);
        }

        $this->line("  Imported <comment>" . count($records) . "</comment> tax rate(s).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Categories
    // -------------------------------------------------------------------------

    private function importCategories(): void
    {
        $this->info('Importing categories…');

        $rows = $this->ps
            ->table('ps_category as c')
            ->join('ps_category_lang as cl', function ($join) {
                $join->on('cl.id_category', '=', 'c.id_category')
                     ->where('cl.id_lang', '=', 1);
            })
            ->where('c.id_category', '>', 1)   // skip virtual root (id=1)
            ->select(
                'c.id_category',
                'c.id_parent',
                'c.active',
                'c.position',
                'c.level_depth',
                'cl.name',
                'cl.description',
            )
            ->orderBy('c.level_depth')
            ->orderBy('c.id_category')
            ->get();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $usedSlugs = [];
        $now = now();

        foreach ($rows as $row) {
            $slug = $this->uniqueSlug((string) $row->name, $usedSlugs);
            $usedSlugs[] = $slug;

            $parentId = ((int) $row->id_parent <= 1) ? null : (int) $row->id_parent;

            DB::table('categories')->insert([
                'id'          => (int) $row->id_category,
                'parent_id'   => $parentId,
                'name'        => (string) $row->name,
                'slug'        => $slug,
                'description' => $this->stripAndNull($row->description),
                'active'      => (bool) $row->active,
                'position'    => (int) $row->position,
                'level_depth' => (int) $row->level_depth,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Imported <comment>{$rows->count()}</comment> categor" . ($rows->count() === 1 ? 'y' : 'ies') . '.');
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Products
    // -------------------------------------------------------------------------

    private function importProducts(): void
    {
        $this->info('Importing products…');

        $rows = $this->ps
            ->table('ps_product as p')
            ->join('ps_product_lang as pl', function ($join) {
                $join->on('pl.id_product', '=', 'p.id_product')
                     ->where('pl.id_lang', '=', 1)
                     ->where('pl.id_shop', '=', 1);
            })
            ->join('ps_product_shop as ps_shop', function ($join) {
                $join->on('ps_shop.id_product', '=', 'p.id_product')
                     ->where('ps_shop.id_shop', '=', 1);
            })
            ->select(
                'p.id_product',
                'p.reference',
                'p.price',
                'p.weight',
                'ps_shop.active',
                'pl.name',
                'pl.description',
                'pl.description_short',
            )
            ->get();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $usedSlugs = [];
        $now = now();

        foreach ($rows as $row) {
            $slug = $this->uniqueSlug((string) $row->name, $usedSlugs);
            $usedSlugs[] = $slug;

            // reference must be unique — fall back to product id if blank
            $reference = (string) ($row->reference ?? '');
            if ($reference === '') {
                $reference = 'PROD-' . $row->id_product;
            }

            DB::table('products')->insert([
                'id'                => (int) $row->id_product,
                'name'              => (string) $row->name,
                'slug'              => $slug,
                'description'       => $this->stripAndNull($row->description),
                'short_description' => $this->stripAndNull($row->description_short),
                'reference'         => $reference,
                'price'             => (float) $row->price,
                'active'            => (bool) $row->active,
                'weight'            => $row->weight !== null ? (float) $row->weight : null,
                'position'          => 0,
                'created_at'        => $now,
                'updated_at'        => $now,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Imported <comment>{$rows->count()}</comment> product(s).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Category–product pivot
    // -------------------------------------------------------------------------

    private function importCategoryProductPivot(): void
    {
        $this->info('Importing category–product relationships…');

        $rows = $this->ps
            ->table('ps_category_product')
            ->whereNotIn('id_category', [1, 2])
            ->select('id_category as category_id', 'id_product as product_id', 'position')
            ->get();

        // Filter to only rows whose category and product we actually imported.
        $importedCategories = DB::table('categories')->pluck('id')->flip()->all();
        $importedProducts   = DB::table('products')->pluck('id')->flip()->all();

        $records = $rows
            ->filter(fn ($r) => isset($importedCategories[$r->category_id]) && isset($importedProducts[$r->product_id]))
            ->map(fn ($r) => [
                'category_id' => (int) $r->category_id,
                'product_id'  => (int) $r->product_id,
                'position'    => (int) $r->position,
            ])
            ->unique(fn ($r) => $r['category_id'] . '-' . $r['product_id'])
            ->values()
            ->all();

        foreach (array_chunk($records, 500) as $chunk) {
            DB::table('category_product')->insert($chunk);
        }

        $this->line("  Imported <comment>" . count($records) . "</comment> category–product link(s).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Product images
    // -------------------------------------------------------------------------

    private function importProductImages(): void
    {
        $this->info('Importing product images…');

        $rows = $this->ps
            ->table('ps_image as i')
            ->join('ps_image_shop as is2', function ($join) {
                $join->on('is2.id_image', '=', 'i.id_image')
                     ->where('is2.id_shop', '=', 1);
            })
            ->select('i.id_image', 'i.id_product', 'i.position', 'is2.cover')
            ->get();

        $importedProducts = DB::table('products')->pluck('id')->flip()->all();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $now = now();
        $chunk = [];

        foreach ($rows as $row) {
            if (!isset($importedProducts[$row->id_product])) {
                $bar->advance();
                continue;
            }

            $isCover = (bool) $row->cover;

            $chunk[] = [
                'product_id' => (int) $row->id_product,
                'filename'   => $row->id_image . '.jpg',
                'position'   => (int) $row->position,
                'is_cover'   => $isCover,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('product_images')->insert($chunk);
                $chunk = [];
            }

            $bar->advance();
        }

        if ($chunk) {
            DB::table('product_images')->insert($chunk);
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Imported <comment>{$rows->count()}</comment> product image(s).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Stock
    // -------------------------------------------------------------------------

    private function importStock(): void
    {
        $this->info('Importing stock…');

        $rows = $this->ps
            ->table('ps_stock_available')
            ->where('id_product_attribute', '=', 0)
            ->select('id_product', 'quantity', 'out_of_stock')
            ->get();

        $importedProducts = DB::table('products')->pluck('id')->flip()->all();

        $now = now();
        $records = $rows
            ->filter(fn ($r) => isset($importedProducts[$r->id_product]))
            ->unique('id_product')
            ->map(fn ($r) => [
                'product_id'          => (int) $r->id_product,
                'quantity'            => (int) $r->quantity,
                'allow_out_of_stock'  => (int) $r->out_of_stock > 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ])
            ->values()
            ->all();

        foreach (array_chunk($records, 500) as $chunk) {
            DB::table('stock_availabilities')->insert($chunk);
        }

        $this->line("  Imported <comment>" . count($records) . "</comment> stock record(s).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Customers
    // -------------------------------------------------------------------------

    private function importCustomers(): void
    {
        $this->info('Importing customers…');

        $rows = $this->ps
            ->table('ps_customer')
            ->where('is_guest', '=', 0)
            ->where('deleted', '=', 0)
            ->select(
                'id_customer',
                'firstname',
                'lastname',
                'email',
                'passwd',
                'active',
                'newsletter',
                'birthday',
            )
            ->get();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $now = now();

        foreach ($rows as $row) {
            $birthday = null;
            if (!empty($row->birthday) && $row->birthday !== '0000-00-00') {
                $birthday = $row->birthday;
            }

            $phone = null;

            // Use DB::table to avoid the model's hashed cast re-hashing the password.
            DB::table('customers')->insert([
                'id'         => (int) $row->id_customer,
                'firstname'  => (string) $row->firstname,
                'lastname'   => (string) $row->lastname,
                'email'      => (string) $row->email,
                'password'   => (string) $row->passwd,   // raw bcrypt from PS
                'phone'      => $phone,
                'active'     => (bool) $row->active,
                'newsletter' => (bool) $row->newsletter,
                'birthday'   => $birthday,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Imported <comment>{$rows->count()}</comment> customer(s).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Addresses
    // -------------------------------------------------------------------------

    private function importAddresses(): void
    {
        $this->info('Importing addresses…');

        $rows = $this->ps
            ->table('ps_address as a')
            ->leftJoin('ps_country as co', 'co.id_country', '=', 'a.id_country')
            ->where('a.deleted', '=', 0)
            ->select(
                'a.id_customer',
                'a.alias',
                'a.firstname',
                'a.lastname',
                'a.company',
                'a.address1',
                'a.address2',
                'a.city',
                'a.postcode',
                'a.phone',
                'a.phone_mobile',
                'co.iso_code',
            )
            ->get();

        $importedCustomers = DB::table('customers')->pluck('id')->flip()->all();

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        $now = now();
        $chunk = [];

        foreach ($rows as $row) {
            // Guest/deleted customer addresses — skip if customer wasn't imported.
            $customerId = ((int) $row->id_customer > 0 && isset($importedCustomers[$row->id_customer]))
                ? (int) $row->id_customer
                : null;

            $phone = $this->coalesceString($row->phone, $row->phone_mobile);

            $chunk[] = [
                'customer_id' => $customerId,
                'alias'       => $this->coalesceString($row->alias) ?? 'Home',
                'firstname'   => (string) $row->firstname,
                'lastname'    => (string) $row->lastname,
                'company'     => $this->coalesceString($row->company),
                'address1'    => (string) $row->address1,
                'address2'    => $this->coalesceString($row->address2),
                'city'        => (string) $row->city,
                'postcode'    => (string) $row->postcode,
                'country'     => $this->coalesceString($row->iso_code) ?? 'ZA',
                'phone'       => $phone,
                'active'      => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];

            if (count($chunk) >= 500) {
                DB::table('addresses')->insert($chunk);
                $chunk = [];
            }

            $bar->advance();
        }

        if ($chunk) {
            DB::table('addresses')->insert($chunk);
        }

        $bar->finish();
        $this->newLine();
        $this->line("  Imported <comment>{$rows->count()}</comment> address(es).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Shipping methods
    // -------------------------------------------------------------------------

    private function importShippingMethods(): void
    {
        $this->info('Importing shipping methods…');

        $rows = $this->ps
            ->table('ps_carrier as c')
            ->leftJoin('ps_carrier_lang as cl', function ($join) {
                $join->on('cl.id_carrier', '=', 'c.id_carrier')
                     ->where('cl.id_lang', '=', 1);
            })
            ->where('c.deleted', '=', 0)
            ->where('c.active', '=', 1)
            ->select(
                'c.id_carrier',
                'c.name as carrier_name',
                'cl.delay',
            )
            ->get();

        $now = now();
        $seenNames = [];
        $records = [];

        foreach ($rows as $row) {
            $name = $this->coalesceString($row->carrier_name) ?? 'Carrier';

            // Deduplicate by name (PS often has many rows for the same carrier).
            if (in_array($name, $seenNames, true)) {
                continue;
            }
            $seenNames[] = $name;

            $records[] = [
                'name'       => $name,
                'delay'      => $this->coalesceString($row->delay),
                'base_price' => '0.00',
                'active'     => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($records) {
            DB::table('shipping_methods')->insert($records);
        }

        $this->line("  Imported <comment>" . count($records) . "</comment> shipping method(s).");
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a URL-safe slug from $name, appending -2, -3, … if already taken.
     *
     * @param  string   $name
     * @param  string[] $usedSlugs  Already-used slugs accumulated during this run.
     * @return string
     */
    private function uniqueSlug(string $name, array $usedSlugs): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'item';
        }

        $candidate = $base;
        $counter   = 2;

        while (in_array($candidate, $usedSlugs, true)) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    /**
     * Strip HTML tags and return null if the result is empty.
     */
    private function stripAndNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $stripped = trim(strip_tags($value));

        return $stripped !== '' ? $stripped : null;
    }

    /**
     * Return the first argument that is a non-empty string, or null.
     */
    private function coalesceString(?string ...$values): ?string
    {
        foreach ($values as $v) {
            if ($v !== null && trim($v) !== '') {
                return $v;
            }
        }

        return null;
    }
}
