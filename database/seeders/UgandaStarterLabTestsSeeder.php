<?php

namespace Database\Seeders;

use App\Models\LabTest;
use Illuminate\Database\Seeder;

/** Seeds the lab tests catalogue with Ugandan starter records and prices. */
class UgandaStarterLabTestsSeeder extends Seeder
{
    /** Upserts the starter laboratory test catalogue and its configured prices. */
    public function run(): void
    {
        $tests = [
            ['FBC', 'Full blood count (CBC)', 'EDTA blood', 20000],
            ['MAL-RDT', 'Malaria rapid diagnostic test', 'Blood', 5000],
            ['MAL-SM', 'Malaria blood smear', 'Blood', 10000],
            ['HIV-RAPID', 'HIV rapid test', 'Blood', 10000],
            ['URINALYSIS', 'Urinalysis', 'Urine', 10000],
            ['STOOL-MIC', 'Stool microscopy', 'Stool', 15000],
            ['RBG', 'Random blood glucose', 'Blood', 5000],
            ['FBS', 'Fasting blood glucose', 'Blood', 7000],
            ['HBA1C', 'HbA1c', 'Blood', 40000],
            ['BLOOD-GROUP', 'Blood group and Rh', 'Blood', 15000],
            ['PREG-URINE', 'Pregnancy test (urine)', 'Urine', 10000],
            ['PREG-SERUM', 'Pregnancy test (serum beta-hCG)', 'Blood', 30000],
            ['SYPHILIS-RPR', 'Syphilis screen (RPR/VDRL)', 'Blood', 15000],
            ['HEP-B', 'Hepatitis B surface antigen', 'Blood', 20000],
            ['HEP-C', 'Hepatitis C antibody', 'Blood', 25000],
            ['CREATININE', 'Serum creatinine', 'Blood', 15000],
            ['UREA', 'Blood urea', 'Blood', 15000],
            ['LFT', 'Liver function tests', 'Blood', 45000],
            ['LIPID', 'Lipid profile', 'Blood', 40000],
            ['WIDAL', 'Widal test', 'Blood', 15000],
            ['TYPHOID-RDT', 'Typhoid rapid test', 'Blood', 15000],
            ['GON-CT', 'Gonorrhoea and chlamydia screen', 'Urine/swab', 50000],
            ['VAG-SWAB', 'High vaginal swab microscopy', 'Vaginal swab', 25000],
            ['SPUTUM-AFB', 'Sputum AFB microscopy', 'Sputum', 15000],
            ['TB-GENE', 'TB molecular test (GeneXpert)', 'Sputum', 80000],
            ['ESR', 'Erythrocyte sedimentation rate (ESR)', 'EDTA blood', 15000],
            ['HAEMOGLOBIN', 'Haemoglobin (Hb)', 'Blood', 10000],
            ['ELECTROLYTES', 'Serum electrolytes', 'Blood', 45000],
            ['URIC-ACID', 'Uric acid', 'Blood', 20000],
            ['OCCULT-BLOOD', 'Stool occult blood', 'Stool', 20000],
        ];

        foreach ($tests as [$code, $name, $specimen, $price]) {
            LabTest::query()->firstOrCreate(['code' => $code], [
                'name' => $name,
                'specimen' => $specimen,
                'price' => $price,
                'is_active' => true,
            ]);
        }
    }
}
