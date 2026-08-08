<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CnclassRuleset;
use App\Models\CnclassProdiMapping;
use App\Helpers\CnClassHelperr;
use Illuminate\Support\Facades\DB;

class CnclassSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::beginTransaction();

        try {
            // Get data from the hardcoded helper for migration
            $method = new \ReflectionMethod(CnClassHelperr::class, 'getHardcodedProdiData');
            $method->setAccessible(true);
            $data = $method->invoke(null);
            
            $rulesets = $data[0] ?? [];
            $aliases = $data[1] ?? [];

            // 1. Insert Rulesets
            $insertedRulesets = [];
            foreach ($rulesets as $name => $rules) {
                // Ensure unique
                $ruleset = CnclassRuleset::firstOrCreate(
                    ['name' => (string)$name],
                    ['rules' => $rules]
                );
                
                $insertedRulesets[$name] = $ruleset->id;

                // If the ruleset name itself is likely a prodi code (e.g., doesn't contain a dash or is short)
                // We map it to itself just in case it doesn't have an alias.
                // In CnClassHelperr, direct keys without aliases are just accessed directly.
                // It's safer to always map the ruleset name to itself if it's not an alias key elsewhere.
                CnclassProdiMapping::firstOrCreate([
                    'prodi_code' => (string)$name,
                    'ruleset_id' => $ruleset->id
                ]);
            }

            // 2. Insert Aliases (Mappings)
            foreach ($aliases as $prodiCode => $rulesetName) {
                if (isset($insertedRulesets[$rulesetName])) {
                    CnclassProdiMapping::updateOrCreate(
                        ['prodi_code' => (string)$prodiCode],
                        ['ruleset_id' => $insertedRulesets[$rulesetName]]
                    );
                }
            }

            DB::commit();
            $this->command->info('Successfully migrated hardcoded CnClass data to database!');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error during migration: ' . $e->getMessage());
        }
    }
}
