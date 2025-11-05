<?php

namespace App\Services;

use App\Models\ServiceType;

class ServiceTypeRuleManager
{
    /**
     * Apply rules based on service_type
     */
    public static function apply(string $serviceTypeId, $component, $model = null)
    {
        $serviceType = \App\Models\ServiceType::find($serviceTypeId)?->name;

        switch (strtolower($serviceType)) {
            case 'evaluvation':
                self::applyEvaluationRules($component, $model);
                break;
            
            case 'service addon':
                self::applyServiceAddonRules($component, $model);
                break;

            case 'pool':
                self::applyPoolRules($component, $model);
                break;

            case 'events':
                self::applyEventsRules($component, $model);
                break;
            
            case 'exercise & energise':
                self::applyExerciseEnergiseRules($component, $model);
                break;

            case 'salon & spa':
                self::applySalonSpaRules($component, $model);
                break;

            case 'limo':
                self::applyLimoRules($component, $model);
                break;

            default:
                self::applyDefaultRules($component, $model);
        }
    }

    private static function applyEvaluationRules($component, $model = null)
    {
        $component->service_addon = false;
        $component->pricing_type = 'fixed';
        $component->pricing_attributes = ['label','price'];
        $component->pet_selection_required = true;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = true;
        $component->disabledFields = ['evaluvation_required', 'is_shippable','pool_id'];
        $component->readOnlyFields = ['pricing_type', 'pricing_attributes'];

        $component->dispatch('set-pricing_attributes', pricing_attributes: $component->pricing_attributes);

        $component->dispatch('disableSelect2');
        // Ensure only one service per species
        if (isset($component->species_id)) {
            $exists = \App\Models\Service::where('species_id', $component->species_id)
                ->where('service_type_id', $component->service_type_id) // Assuming 1 is the ID for Evaluation service type
                ->when($model, fn($q) => $q->where('id', '!=', $model))
                ->whereNull('deleted_at')
                ->where('service_addon', false)
                ->where('parent_id', null)
                ->exists();

            if ($exists) {
                throw new \Exception('Only one Evaluation service is allowed per species.');
            }
        }
    }

    private static function applyServiceAddonRules($component, $model = null)
    {
        $component->service_addon = true;
        $component->pricing_type = 'fixed';
        $component->pricing_attributes = ['label','price'];
        $component->pet_selection_required = false;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = true;
        $component->readOnlyFields = ['pricing_type', 'pricing_attributes'];
        $component->disabledFields = ['evaluvation_required', 'is_shippable','pet_selection_required'];

        $component->dispatch('set-pricing_attributes', pricing_attributes: $component->pricing_attributes);

        $component->dispatch('disableSelect2');
    }

    private static function applyPoolRules($component, $model = null)
    {
        $component->service_addon = false;
        $component->pricing_type = 'fixed';
        $component->pricing_attributes = ['label','duration','price'];
        $component->pet_selection_required = false;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = false;
        $component->disabledFields = ['is_shippable'];
        $component->readOnlyFields = ['pricing_type', 'pricing_attributes','pool'];

        $component->dispatch('set-pricing_attributes', pricing_attributes: $component->pricing_attributes);

        $component->dispatch('disableSelect2');
    }

    private static function applyEventsRules($component, $model = null)
    {
        $component->service_addon = false;
        $component->pricing_type = 'fixed';
        $component->pricing_attributes = ['label','duration','no_pets','no_humans','price'];
        $component->pet_selection_required = false;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = true;
        $component->disabledFields = ['is_shippable'];
        $component->readOnlyFields = ['pricing_type', 'pricing_attributes'];

        $component->dispatch('set-pricing_attributes', pricing_attributes: $component->pricing_attributes);

        $component->dispatch('disableSelect2');
    }

    private static function applyExerciseEnergiseRules($component, $model = null)
    {
        $component->service_addon = false;
        $component->pricing_type = 'fixed';
        $component->pricing_attributes = ['label','duration','price'];
        $component->pet_selection_required = false;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = true;
        $component->disabledFields = ['is_shippable'];
        $component->readOnlyFields = ['pricing_type', 'pricing_attributes'];

        $component->dispatch('set-pricing_attributes', pricing_attributes: $component->pricing_attributes);

        $component->dispatch('disableSelect2');
    }

    private static function applySalonSpaRules($component, $model = null)
    {
        $component->service_addon = false;
        $component->pricing_type = 'advance';
        $component->pricing_attributes = ['label','price'];
        $component->pet_selection_required = false;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = true;
        $component->disabledFields = ['is_shippable'];
        $component->readOnlyFields = ['pricing_type', 'pricing_attributes'];

        $component->dispatch('set-pricing_attributes', pricing_attributes: $component->pricing_attributes);

        $component->dispatch('disableSelect2');
    }

    private static function applyLimoRules($component, $model = null)
    {
        $component->service_addon = false;
        $component->pricing_type = 'distance_based';
        $component->pricing_attributes = ['label','price','km_start','km_end'];
        $component->pet_selection_required = false;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = false;
        $component->disabledFields = ['is_shippable'];
        $component->readOnlyFields = ['pricing_type', 'pricing_attributes','limo_type'];

        $component->dispatch('set-pricing_attributes', pricing_attributes: $component->pricing_attributes);

        $component->dispatch('disableSelect2');
    }

    private static function applyDefaultRules($component, $model = null)
    {
        $component->service_addon = false;
        $component->pet_selection_required = false;
        $component->evaluvation_required = false;
        $component->is_shippable = false;
        $component->showDiv = true;
        $component->disabledFields = [];
        $component->readOnlyFields = [];

        $component->dispatch('enableSelect2');
        
    }
}
