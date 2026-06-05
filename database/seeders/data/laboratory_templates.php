<?php

return [
    [
        'test_name' => 'CBC',
        'test_code' => 'CBC',
        'fields' => [
            ['field_label' => 'Hemoglobin', 'field_type' => 'number', 'unit' => 'g/dL', 'reference_range' => 'Male: 13.0-17.0; Female: 12.0-15.0', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'TLC / WBC Count', 'field_type' => 'number', 'unit' => '/cmm', 'reference_range' => '4,000-11,000', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'RBC Count', 'field_type' => 'number', 'unit' => 'million/cmm', 'reference_range' => 'Male: 4.5-5.9; Female: 4.1-5.1', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'Hematocrit / PCV', 'field_type' => 'number', 'unit' => '%', 'reference_range' => 'Male: 40-50; Female: 36-44', 'options' => [], 'sort_order' => 4],
            ['field_label' => 'MCV', 'field_type' => 'number', 'unit' => 'fL', 'reference_range' => '80-100', 'options' => [], 'sort_order' => 5],
            ['field_label' => 'MCH', 'field_type' => 'number', 'unit' => 'pg', 'reference_range' => '27-33', 'options' => [], 'sort_order' => 6],
            ['field_label' => 'MCHC', 'field_type' => 'number', 'unit' => 'g/dL', 'reference_range' => '32-36', 'options' => [], 'sort_order' => 7],
            ['field_label' => 'Platelets', 'field_type' => 'number', 'unit' => '/cmm', 'reference_range' => '150,000-450,000', 'options' => [], 'sort_order' => 8],
            ['field_label' => 'Neutrophils', 'field_type' => 'number', 'unit' => '%', 'reference_range' => '40-75', 'options' => [], 'sort_order' => 9],
            ['field_label' => 'Lymphocytes', 'field_type' => 'number', 'unit' => '%', 'reference_range' => '20-45', 'options' => [], 'sort_order' => 10],
            ['field_label' => 'Monocytes', 'field_type' => 'number', 'unit' => '%', 'reference_range' => '2-10', 'options' => [], 'sort_order' => 11],
            ['field_label' => 'Eosinophils', 'field_type' => 'number', 'unit' => '%', 'reference_range' => '1-6', 'options' => [], 'sort_order' => 12],
            ['field_label' => 'Basophils', 'field_type' => 'number', 'unit' => '%', 'reference_range' => '0-1', 'options' => [], 'sort_order' => 13],
            ['field_label' => 'ESR', 'field_type' => 'number', 'unit' => 'mm/hr', 'reference_range' => 'Male: 0-15; Female: 0-20', 'options' => [], 'sort_order' => 14],
        ],
    ],
    [
        'test_name' => 'Blood Sugar',
        'test_code' => 'BS',
        'fields' => [
            ['field_label' => 'Random Blood Sugar', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '70-140', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Fasting Blood Sugar', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '70-100', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'Post Prandial Blood Sugar', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '<140', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'HbA1c', 'field_type' => 'number', 'unit' => '%', 'reference_range' => '<5.7', 'options' => [], 'sort_order' => 4],
        ],
    ],
    [
        'test_name' => 'Urine Complete Examination',
        'test_code' => 'UCE',
        'fields' => [
            ['field_label' => 'Color', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Pale yellow to amber', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Appearance', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Clear', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'Specific Gravity', 'field_type' => 'text', 'unit' => null, 'reference_range' => '1.005-1.030', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'pH', 'field_type' => 'number', 'unit' => null, 'reference_range' => '4.5-8.0', 'options' => [], 'sort_order' => 4],
            ['field_label' => 'Protein', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative', 'options' => [], 'sort_order' => 5],
            ['field_label' => 'Sugar', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative', 'options' => [], 'sort_order' => 6],
            ['field_label' => 'Ketones', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative', 'options' => [], 'sort_order' => 7],
            ['field_label' => 'Bilirubin', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative', 'options' => [], 'sort_order' => 8],
            ['field_label' => 'Urobilinogen', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Normal', 'options' => [], 'sort_order' => 9],
            ['field_label' => 'Nitrite', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative', 'options' => [], 'sort_order' => 10],
            ['field_label' => 'Leukocytes', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative', 'options' => [], 'sort_order' => 11],
            ['field_label' => 'Pus Cells', 'field_type' => 'text', 'unit' => '/HPF', 'reference_range' => '0-5', 'options' => [], 'sort_order' => 12],
            ['field_label' => 'RBCs', 'field_type' => 'text', 'unit' => '/HPF', 'reference_range' => '0-2', 'options' => [], 'sort_order' => 13],
            ['field_label' => 'Epithelial Cells', 'field_type' => 'text', 'unit' => '/HPF', 'reference_range' => 'Few', 'options' => [], 'sort_order' => 14],
            ['field_label' => 'Crystals', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Nil', 'options' => [], 'sort_order' => 15],
            ['field_label' => 'Bacteria', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Nil', 'options' => [], 'sort_order' => 16],
        ],
    ],
    [
        'test_name' => 'LFT',
        'test_code' => 'LFT',
        'fields' => [
            ['field_label' => 'Total Bilirubin', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '0.1-1.2', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Direct Bilirubin', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '0.0-0.3', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'Indirect Bilirubin', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '0.2-0.9', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'ALT / SGPT', 'field_type' => 'number', 'unit' => 'U/L', 'reference_range' => '7-56', 'options' => [], 'sort_order' => 4],
            ['field_label' => 'AST / SGOT', 'field_type' => 'number', 'unit' => 'U/L', 'reference_range' => '10-40', 'options' => [], 'sort_order' => 5],
            ['field_label' => 'Alkaline Phosphatase', 'field_type' => 'number', 'unit' => 'U/L', 'reference_range' => '44-147', 'options' => [], 'sort_order' => 6],
            ['field_label' => 'Total Protein', 'field_type' => 'number', 'unit' => 'g/dL', 'reference_range' => '6.0-8.3', 'options' => [], 'sort_order' => 7],
            ['field_label' => 'Albumin', 'field_type' => 'number', 'unit' => 'g/dL', 'reference_range' => '3.5-5.0', 'options' => [], 'sort_order' => 8],
            ['field_label' => 'Globulin', 'field_type' => 'number', 'unit' => 'g/dL', 'reference_range' => '2.0-3.5', 'options' => [], 'sort_order' => 9],
            ['field_label' => 'A/G Ratio', 'field_type' => 'number', 'unit' => null, 'reference_range' => '1.0-2.2', 'options' => [], 'sort_order' => 10],
        ],
    ],
    [
        'test_name' => 'RFT',
        'test_code' => 'RFT',
        'fields' => [
            ['field_label' => 'Urea', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '15-40', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Blood Urea Nitrogen', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '7-20', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'Creatinine', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => 'Male: 0.7-1.3; Female: 0.6-1.1', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'Uric Acid', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => 'Male: 3.4-7.0; Female: 2.4-6.0', 'options' => [], 'sort_order' => 4],
            ['field_label' => 'Sodium', 'field_type' => 'number', 'unit' => 'mmol/L', 'reference_range' => '135-145', 'options' => [], 'sort_order' => 5],
            ['field_label' => 'Potassium', 'field_type' => 'number', 'unit' => 'mmol/L', 'reference_range' => '3.5-5.1', 'options' => [], 'sort_order' => 6],
            ['field_label' => 'Chloride', 'field_type' => 'number', 'unit' => 'mmol/L', 'reference_range' => '98-107', 'options' => [], 'sort_order' => 7],
            ['field_label' => 'Calcium', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '8.5-10.5', 'options' => [], 'sort_order' => 8],
        ],
    ],
    [
        'test_name' => 'Lipid Profile',
        'test_code' => 'LIPID',
        'fields' => [
            ['field_label' => 'Total Cholesterol', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '<200', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Triglycerides', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '<150', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'HDL Cholesterol', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => 'Male: >40; Female: >50', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'LDL Cholesterol', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '<100', 'options' => [], 'sort_order' => 4],
            ['field_label' => 'VLDL Cholesterol', 'field_type' => 'number', 'unit' => 'mg/dL', 'reference_range' => '5-40', 'options' => [], 'sort_order' => 5],
            ['field_label' => 'Cholesterol / HDL Ratio', 'field_type' => 'number', 'unit' => null, 'reference_range' => '<5.0', 'options' => [], 'sort_order' => 6],
        ],
    ],
    [
        'test_name' => 'Typhoid',
        'test_code' => 'TYP',
        'fields' => [
            ['field_label' => 'Typhi O', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative / <1:80', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Typhi H', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative / <1:80', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'Paratyphi AH', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative / <1:80', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'Paratyphi BH', 'field_type' => 'text', 'unit' => null, 'reference_range' => 'Negative / <1:80', 'options' => [], 'sort_order' => 4],
            ['field_label' => 'Interpretation', 'field_type' => 'textarea', 'unit' => null, 'reference_range' => 'Negative', 'options' => [], 'sort_order' => 5],
        ],
    ],
    [
        'test_name' => 'Dengue',
        'test_code' => 'DEN',
        'fields' => [
            ['field_label' => 'Dengue NS1 Antigen', 'field_type' => 'select', 'unit' => null, 'reference_range' => 'Negative', 'options' => ['Positive', 'Negative'], 'sort_order' => 1],
            ['field_label' => 'Dengue IgM', 'field_type' => 'select', 'unit' => null, 'reference_range' => 'Negative', 'options' => ['Positive', 'Negative'], 'sort_order' => 2],
            ['field_label' => 'Dengue IgG', 'field_type' => 'select', 'unit' => null, 'reference_range' => 'Negative', 'options' => ['Positive', 'Negative'], 'sort_order' => 3],
            ['field_label' => 'Platelet Count', 'field_type' => 'number', 'unit' => '/cmm', 'reference_range' => '150,000-450,000', 'options' => [], 'sort_order' => 4],
        ],
    ],
    [
        'test_name' => 'HBsAg',
        'test_code' => 'HBSAG',
        'fields' => [
            ['field_label' => 'HBsAg', 'field_type' => 'select', 'unit' => null, 'reference_range' => 'Negative', 'options' => ['Positive', 'Negative'], 'sort_order' => 1],
        ],
    ],
    [
        'test_name' => 'HCV',
        'test_code' => 'HCV',
        'fields' => [
            ['field_label' => 'Anti-HCV', 'field_type' => 'select', 'unit' => null, 'reference_range' => 'Negative', 'options' => ['Positive', 'Negative'], 'sort_order' => 1],
        ],
    ],
    [
        'test_name' => 'Pregnancy Test',
        'test_code' => 'UPT',
        'fields' => [
            ['field_label' => 'Urine Pregnancy Test', 'field_type' => 'select', 'unit' => null, 'reference_range' => 'Negative', 'options' => ['Positive', 'Negative'], 'sort_order' => 1],
        ],
    ],
    [
        'test_name' => 'Blood Group',
        'test_code' => 'BG',
        'fields' => [
            ['field_label' => 'ABO Group', 'field_type' => 'select', 'unit' => null, 'reference_range' => null, 'options' => ['A', 'B', 'AB', 'O'], 'sort_order' => 1],
            ['field_label' => 'Rh Factor', 'field_type' => 'select', 'unit' => null, 'reference_range' => null, 'options' => ['Positive', 'Negative'], 'sort_order' => 2],
        ],
    ],
    [
        'test_name' => 'CRP',
        'test_code' => 'CRP',
        'fields' => [
            ['field_label' => 'CRP', 'field_type' => 'number', 'unit' => 'mg/L', 'reference_range' => '<5', 'options' => [], 'sort_order' => 1],
        ],
    ],
    [
        'test_name' => 'Serum Electrolytes',
        'test_code' => 'SE',
        'fields' => [
            ['field_label' => 'Sodium', 'field_type' => 'number', 'unit' => 'mmol/L', 'reference_range' => '135-145', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Potassium', 'field_type' => 'number', 'unit' => 'mmol/L', 'reference_range' => '3.5-5.1', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'Chloride', 'field_type' => 'number', 'unit' => 'mmol/L', 'reference_range' => '98-107', 'options' => [], 'sort_order' => 3],
            ['field_label' => 'Bicarbonate', 'field_type' => 'number', 'unit' => 'mmol/L', 'reference_range' => '22-29', 'options' => [], 'sort_order' => 4],
        ],
    ],
    [
        'test_name' => 'Thyroid Function Test',
        'test_code' => 'TFT',
        'fields' => [
            ['field_label' => 'TSH', 'field_type' => 'number', 'unit' => 'uIU/mL', 'reference_range' => '0.4-4.0', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'T3', 'field_type' => 'number', 'unit' => 'ng/dL', 'reference_range' => '80-180', 'options' => [], 'sort_order' => 2],
            ['field_label' => 'T4', 'field_type' => 'number', 'unit' => 'ug/dL', 'reference_range' => '4.5-12.5', 'options' => [], 'sort_order' => 3],
        ],
    ],
    [
        'test_name' => 'Serum Amylase Lipase',
        'test_code' => 'AMLIP',
        'fields' => [
            ['field_label' => 'Serum Amylase', 'field_type' => 'number', 'unit' => 'U/L', 'reference_range' => '30-110', 'options' => [], 'sort_order' => 1],
            ['field_label' => 'Serum Lipase', 'field_type' => 'number', 'unit' => 'U/L', 'reference_range' => '0-160', 'options' => [], 'sort_order' => 2],
        ],
    ],
    [
        'test_name' => 'X-Ray',
        'test_code' => 'XR',
        'test_type' => 'imaging',
        'description' => 'Radiographic imaging study with image upload.',
        'fields' => [
            ['field_label' => 'Description / Findings', 'field_type' => 'textarea', 'unit' => null, 'reference_range' => null, 'options' => [], 'placeholder' => 'Enter radiological findings or description...', 'sort_order' => 1],
            ['field_label' => 'X-Ray Image', 'field_type' => 'image', 'unit' => null, 'reference_range' => null, 'options' => [], 'is_required' => true, 'sort_order' => 2],
        ],
    ],
];
