<?php

class PharmacyClinicalHelper {
    public static function getFormConfigs() {
        return [
            'Vaccine' => [
                'has_duration' => false,
                'has_frequency' => false,
                'is_dose_editable' => false,
                'default_dose' => 1,
                'default_freq' => 1,
                'default_dur' => 1,
                'formula' => 'fixed_1',
                'description_template' => 'Vaccine: {drug_name} (1 {dispense_unit} [{strength}])'
            ],
            'Injection' => [
                'has_duration' => false,
                'has_frequency' => true,
                'is_dose_editable' => true,
                'default_dose' => 1,
                'default_freq' => 1,
                'default_dur' => 1,
                'formula' => 'dose * frequency',
                'description_template' => 'Medication: {drug_name} ({total_admin} {admin_unit}s -> {qty} {dispense_unit}s)'
            ],
            'Syrup' => [
                'has_duration' => true,
                'has_frequency' => true,
                'is_dose_editable' => true,
                'default_dose' => 5,
                'default_freq' => 3,
                'default_dur' => 7,
                'formula' => 'dose * frequency * duration',
                'description_template' => 'Medication: {drug_name} ({total_admin} {admin_unit}s -> {qty} {dispense_unit}s)'
            ],
            'Tablet' => [
                'has_duration' => true,
                'has_frequency' => true,
                'is_dose_editable' => true,
                'default_dose' => 1,
                'default_freq' => 3,
                'default_dur' => 5,
                'formula' => 'dose * frequency * duration',
                'description_template' => 'Medication: {drug_name} ({total_admin} {admin_unit}s -> {qty} {dispense_unit}s)'
            ],
            'Capsule' => [
                'has_duration' => true,
                'has_frequency' => true,
                'is_dose_editable' => true,
                'default_dose' => 1,
                'default_freq' => 3,
                'default_dur' => 5,
                'formula' => 'dose * frequency * duration',
                'description_template' => 'Medication: {drug_name} ({total_admin} {admin_unit}s -> {qty} {dispense_unit}s)'
            ],
            'Cream' => [
                'has_duration' => false,
                'has_frequency' => false,
                'is_dose_editable' => false,
                'default_dose' => 1,
                'default_freq' => 1,
                'default_dur' => 1,
                'formula' => 'fixed_1',
                'description_template' => 'Topical: {drug_name} (1 {dispense_unit})'
            ],
            'IV Fluid' => [
                'has_duration' => false,
                'has_frequency' => false,
                'is_dose_editable' => true,
                'default_dose' => 1,
                'default_freq' => 1,
                'default_dur' => 1,
                'formula' => 'dose',
                'description_template' => 'IV Fluid: {drug_name} ({qty} {dispense_unit}s)'
            ]
        ];
    }

    public static function calculate($form_type, $dose, $freq, $dur, $pack_size = 1) {
        $configs = self::getFormConfigs();
        $config = $configs[$form_type] ?? $configs['Tablet'];

        $total_admin = 0;
        $daily_dose = 0;
        $qty = 0;

        switch($config['formula']) {
            case 'fixed_1':
                $total_admin = 1;
                $daily_dose = 1;
                $qty = 1;
                break;
            case 'dose * frequency':
                $total_admin = $dose * $freq;
                $daily_dose = $dose * $freq;
                $qty = $total_admin; // Unit-driven: qty is directly total admin
                break;
            case 'dose':
                $total_admin = $dose;
                $daily_dose = $dose;
                $qty = $total_admin;
                break;
            case 'dose * frequency * duration':
            default:
                $total_admin = $dose * $freq * $dur;
                $daily_dose = $dose * $freq;
                $qty = $total_admin;
                break;
        }

        return [
            'total_admin' => round($total_admin, 2),
            'daily_dose' => round($daily_dose, 2),
            'quantity' => round($qty, 2)
        ];
    }

    public static function formatDescription($form_type, $data) {
        $configs = self::getFormConfigs();
        $config = $configs[$form_type] ?? $configs['Tablet'];
        $template = $config['description_template'];

        foreach($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
}
