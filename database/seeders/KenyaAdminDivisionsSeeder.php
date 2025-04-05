<?php

namespace Database\Seeders;

use App\Models\County;
use App\Models\SubCounty;
use App\Models\Constituency;
use Illuminate\Database\Seeder;

class KenyaAdminDivisionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data for a few counties in Kenya
        $counties = [
            ['name' => 'Nairobi', 'code' => '047'],
            ['name' => 'Mombasa', 'code' => '001'],
            ['name' => 'Kisumu', 'code' => '042'],
            ['name' => 'Nakuru', 'code' => '032'],
            ['name' => 'Kiambu', 'code' => '022'],
        ];

        foreach ($counties as $countyData) {
            $county = County::create($countyData);
            
            // Add sub-counties for each county
            $subCounties = $this->getSubCountiesForCounty($county->name);
            
            foreach ($subCounties as $subCountyName) {
                $subCounty = SubCounty::create([
                    'name' => $subCountyName,
                    'county_id' => $county->id,
                ]);
                
                // Add constituencies for each sub-county
                $constituencies = $this->getConstituenciesForSubCounty($subCountyName);
                
                foreach ($constituencies as $constituencyName) {
                    Constituency::create([
                        'name' => $constituencyName,
                        'sub_county_id' => $subCounty->id,
                    ]);
                }
            }
        }
    }
    
    /**
     * Get sub-counties for a specific county.
     */
    private function getSubCountiesForCounty(string $countyName): array
    {
        $subCounties = [
            'Nairobi' => ['Westlands', 'Dagoretti', 'Langata', 'Kibra', 'Roysambu', 'Kasarani', 'Ruaraka', 'Embakasi'],
            'Mombasa' => ['Nyali', 'Kisauni', 'Likoni', 'Mvita', 'Changamwe', 'Jomvu'],
            'Kisumu' => ['Kisumu East', 'Kisumu West', 'Kisumu Central', 'Nyando', 'Muhoroni', 'Nyakach', 'Seme'],
            'Nakuru' => ['Nakuru Town East', 'Nakuru Town West', 'Naivasha', 'Gilgil', 'Kuresoi', 'Molo', 'Rongai', 'Subukia'],
            'Kiambu' => ['Thika', 'Ruiru', 'Juja', 'Kiambu', 'Kiambaa', 'Githunguri', 'Limuru', 'Kikuyu', 'Kabete', 'Gatundu'],
        ];
        
        return $subCounties[$countyName] ?? [];
    }
    
    /**
     * Get constituencies for a specific sub-county.
     */
    private function getConstituenciesForSubCounty(string $subCountyName): array
    {
        $constituencies = [
            'Westlands' => ['Westlands'],
            'Dagoretti' => ['Dagoretti North', 'Dagoretti South'],
            'Langata' => ['Langata'],
            'Kibra' => ['Kibra'],
            'Roysambu' => ['Roysambu'],
            'Kasarani' => ['Kasarani'],
            'Ruaraka' => ['Ruaraka'],
            'Embakasi' => ['Embakasi North', 'Embakasi South', 'Embakasi Central', 'Embakasi East', 'Embakasi West'],
            'Nyali' => ['Nyali'],
            'Kisauni' => ['Kisauni'],
            'Likoni' => ['Likoni'],
            'Mvita' => ['Mvita'],
            'Changamwe' => ['Changamwe'],
            'Jomvu' => ['Jomvu'],
            'Kisumu East' => ['Kisumu East'],
            'Kisumu West' => ['Kisumu West'],
            'Kisumu Central' => ['Kisumu Central'],
            'Nyando' => ['Nyando'],
            'Muhoroni' => ['Muhoroni'],
            'Nyakach' => ['Nyakach'],
            'Seme' => ['Seme'],
            'Nakuru Town East' => ['Nakuru Town East'],
            'Nakuru Town West' => ['Nakuru Town West'],
            'Naivasha' => ['Naivasha'],
            'Gilgil' => ['Gilgil'],
            'Kuresoi' => ['Kuresoi North', 'Kuresoi South'],
            'Molo' => ['Molo'],
            'Rongai' => ['Rongai'],
            'Subukia' => ['Subukia'],
            'Thika' => ['Thika Town'],
            'Ruiru' => ['Ruiru'],
            'Juja' => ['Juja'],
            'Kiambu' => ['Kiambu'],
            'Kiambaa' => ['Kiambaa'],
            'Githunguri' => ['Githunguri'],
            'Limuru' => ['Limuru'],
            'Kikuyu' => ['Kikuyu'],
            'Kabete' => ['Kabete'],
            'Gatundu' => ['Gatundu North', 'Gatundu South'],
        ];
        
        return $constituencies[$subCountyName] ?? [];
    }
}
