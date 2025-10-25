 @component('components.textbox-component', [
    'wireModel' => 'microchip_number',
    'id' => 'microchip_number',
    'label' => 'Pets Microchip number',
    'star' => false,
    'error' => $errors->first('microchip_number'),
    'placeholder' => "Enter your pet's microchip number",
    'placeholder_text' => "Optional for cats, required for dogs."
])
@endcomponent

@component('components.textbox-component', [
    'wireModel' => 'length_cm',
    'id' => 'length_cm',
    'label' => 'Length(cm)',
    'star' => false,
    'error' => $errors->first('length_cm'),
    'placeholder' => "Enter your pet's length",
    'placeholder_text' => "Measure from nose to the base of the tail.",
    'info' => "If the length measurement is taken when a pet is under 12 months old, it should be retaken once they reach 12 months to ensure accuracy."
])
@endcomponent
@component('components.textbox-component', [
    'wireModel' => 'height_cm',
    'id' => 'height_cm',
    'label' => 'Height(cm)',
    'star' => false,
    'error' => $errors->first('height_cm'),
    'placeholder' => "Enter your pet's height",
    'placeholder_text' => "Measure from the ground to the top of the pet’s shoulders (withers)."
])
@endcomponent
@component('components.textbox-component', [
    'wireModel' => 'weight_kg',
    'id' => 'weight_kg',
    'label' => 'Weight(kg)',
    'star' => false,
    'error' => $errors->first('weight_kg'),
    'placeholder' => "Enter your pet's weight",
])
@endcomponent
@component('components.textbox-component', [
    'wireModel' => 'avs_license_number',
    'id' => 'avs_license_number',
    'label' => 'AVS License/Registration Number',
    'star' => false,
    'error' => $errors->first('avs_license_number'),
    'placeholder' => "Enter AVS License/Registration Number",
])
@endcomponent

@component('components.file-upload-component', [
    'wireModel' => 'document',
    'id' => 'document',
    'label' => 'Add File/document',
    'star' => false,
    'src' => $src_document,
    'error' => $errors->first('document'),
    ])
@endcomponent

@component('components.date-component', [
    'wireModel' => 'date_expiry',
    'id' => 'date_expiry',
    'label' => 'AVS Date of Expiry',
    'star' => false,
    'readonly' => false,
    'error' => $errors->first('date_expiry'),
    'wireChangeFn' => 'dateExpiry()',
    'min' => date('Y-m-d')
    ])
@endcomponent
@component('components.dropdown-component', [
    'wireModel' => 'avs_license_expiry',
    'id' => 'avs_license_expiry',
    'label' => 'AVS License Expiry',
    'star' => false,
    'options' => [['value' => 'false','option' => 'Yes'],['value' => 'true','option' => 'No']],
    'error' => $errors->first('avs_license_expiry'),
    'placeholder_text' => "Select avs license expiry",
    'disabled' => true
])
@endcomponent



