<?php

namespace Database\Seeders;

use App\Models\Medicine;
use Illuminate\Database\Seeder;

/** Seeds the medicines catalogue with Ugandan starter records and prices. */
class UgandaStarterMedicinesSeeder extends Seeder
{
    /** Upserts the starter medicine catalogue, prices, and opening stock quantities. */
    public function run(): void
    {
        $medicines = [
            ['PARA500-TAB', 'Paracetamol', '500 mg', 'Tablet', 100, 100, 20],
            ['IBU400-TAB', 'Ibuprofen', '400 mg', 'Tablet', 200, 80, 20],
            ['DICLO50-TAB', 'Diclofenac sodium', '50 mg', 'Tablet', 250, 60, 15],
            ['ORS-20G-SACH', 'Oral rehydration salts', '20.5 g', 'Sachet', 1500, 30, 10],
            ['ZINC20-TAB', 'Zinc sulfate', '20 mg', 'Tablet', 150, 100, 20],
            ['AMOX500-CAP', 'Amoxicillin', '500 mg', 'Capsule', 500, 100, 20],
            ['AMOX125-SUSP', 'Amoxicillin', '125 mg/5 ml', 'Suspension bottle', 4500, 20, 5],
            ['AMOX250-SUSP', 'Amoxicillin', '250 mg/5 ml', 'Suspension bottle', 6000, 15, 5],
            ['AZITH500-TAB', 'Azithromycin', '500 mg', 'Tablet', 1500, 40, 10],
            ['AZITH200-SUSP', 'Azithromycin', '200 mg/5 ml', 'Suspension bottle', 6000, 15, 5],
            ['METRO400-TAB', 'Metronidazole', '400 mg', 'Tablet', 250, 80, 20],
            ['AL-20-120-24', 'Artemether/lumefantrine', '20 mg/120 mg', '24-tablet treatment pack', 12000, 30, 10],
            ['AL-SUSP-60', 'Artemether/lumefantrine', '15 mg/90 mg per 5 ml', 'Suspension bottle', 10000, 10, 3],
            ['ALBEN400-TAB', 'Albendazole', '400 mg', 'Tablet', 500, 60, 15],
            ['ALBEN-SUSP', 'Albendazole', '200 mg/5 ml', 'Suspension bottle', 2500, 20, 5],
            ['MEBEN500-TAB', 'Mebendazole', '500 mg', 'Tablet', 700, 50, 10],
            ['COTRI960-TAB', 'Co-trimoxazole', '960 mg', 'Tablet', 500, 60, 15],
            ['DOXY100-CAP', 'Doxycycline', '100 mg', 'Capsule', 300, 60, 15],
            ['CIPRO500-TAB', 'Ciprofloxacin', '500 mg', 'Tablet', 700, 50, 10],
            ['CEFIX200-TAB', 'Cefixime', '200 mg', 'Tablet', 1800, 30, 10],
            ['CEFTRI1G-VIAL', 'Ceftriaxone', '1 g', 'Injection vial', 4000, 20, 5],
            ['AMPI500-CAP', 'Ampicillin', '500 mg', 'Capsule', 500, 50, 10],
            ['OMEP20-CAP', 'Omeprazole', '20 mg', 'Capsule', 250, 80, 20],
            ['PANTO40-TAB', 'Pantoprazole', '40 mg', 'Tablet', 700, 50, 10],
            ['CETIR10-TAB', 'Cetirizine', '10 mg', 'Tablet', 250, 60, 15],
            ['LORA10-TAB', 'Loratadine', '10 mg', 'Tablet', 300, 60, 15],
            ['CHLOR4-TAB', 'Chlorpheniramine', '4 mg', 'Tablet', 150, 60, 15],
            ['AMLO5-TAB', 'Amlodipine', '5 mg', 'Tablet', 300, 100, 20],
            ['AMLO10-TAB', 'Amlodipine', '10 mg', 'Tablet', 500, 60, 15],
            ['LOS50-TAB', 'Losartan', '50 mg', 'Tablet', 700, 60, 15],
            ['HCTZ25-TAB', 'Hydrochlorothiazide', '25 mg', 'Tablet', 200, 60, 15],
            ['ENAL10-TAB', 'Enalapril', '10 mg', 'Tablet', 300, 60, 15],
            ['ATEN100-TAB', 'Atenolol', '100 mg', 'Tablet', 500, 60, 15],
            ['METF500-TAB', 'Metformin', '500 mg', 'Tablet', 250, 100, 20],
            ['METF850-TAB', 'Metformin', '850 mg', 'Tablet', 400, 80, 20],
            ['GLIC80-TAB', 'Gliclazide', '80 mg', 'Tablet', 500, 60, 15],
            ['INSULIN-HUMAN', 'Human insulin', '100 IU/ml', '10 ml vial', 18000, 10, 3],
            ['ATOR10-TAB', 'Atorvastatin', '10 mg', 'Tablet', 500, 60, 15],
            ['ASP75-TAB', 'Acetylsalicylic acid', '75 mg', 'Tablet', 150, 60, 15],
            ['FERROFOL-TAB', 'Ferrous sulfate + folic acid', '150 mg + 0.4 mg', 'Tablet', 200, 100, 20],
            ['FOLIC5-TAB', 'Folic acid', '5 mg', 'Tablet', 200, 100, 20],
            ['CALCIUM500-TAB', 'Calcium carbonate', '500 mg', 'Tablet', 300, 60, 15],
            ['MULTIVIT-TAB', 'Multivitamin', 'Adult', 'Tablet', 250, 60, 15],
            ['VITC500-TAB', 'Vitamin C', '500 mg', 'Tablet', 300, 60, 15],
            ['SALBUT100-INH', 'Salbutamol', '100 mcg/dose', 'Inhaler', 12000, 10, 3],
            ['PRED5-TAB', 'Prednisolone', '5 mg', 'Tablet', 200, 60, 15],
            ['FLUCON150-CAP', 'Fluconazole', '150 mg', 'Capsule', 1500, 30, 10],
            ['CLOTRI1-CREAM', 'Clotrimazole', '1%', '20 g cream tube', 3500, 15, 5],
            ['ACICLOVIR400-TAB', 'Aciclovir', '400 mg', 'Tablet', 700, 40, 10],
            ['CHLOR-EYE-DROP', 'Chloramphenicol', '0.5%', '10 ml eye drop bottle', 2500, 15, 5],
            ['POVIDONE10-SOL', 'Povidone iodine', '10%', '100 ml solution bottle', 5000, 15, 5],
        ];

        foreach ($medicines as [$sku, $name, $strength, $form, $price, $stock, $reorderLevel]) {
            Medicine::query()->firstOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'strength' => $strength,
                    'dosage_form' => $form,
                    'unit_price' => $price,
                    'stock_quantity' => $stock,
                    'reserved_quantity' => 0,
                    'reorder_level' => $reorderLevel,
                    'is_active' => true,
                ],
            );
        }
    }
}
