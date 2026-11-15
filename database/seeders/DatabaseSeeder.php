<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('plans')->updateOrInsert(['code' => 'free'], ['name' => 'Local development', 'limits' => json_encode(['records_per_module' => 1000, 'members' => 5]), 'created_at' => now(), 'updated_at' => now()]);
    }
}
