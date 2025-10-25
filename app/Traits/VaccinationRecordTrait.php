<?php

namespace App\Traits;

use App\Services\VaccinationRecordService;
use App\Models\VaccinationRecord;
use Auth;


trait VaccinationRecordTrait
{
    public $species, $breeds = [];

    protected $vaccinationRecordService;

    public function boot(VaccinationRecordService $vaccinationRecordService)
    {
        $this->vaccinationRecordService = $vaccinationRecordService;
    }

    public function cannotVaccinate()
    {
        $this->resetValidation();
        $this->reset(['vaccination_id', 'date', 'notes','document','src']);
        $this->cannot_vaccinate = $this->cannot_vaccinate;
    }

    protected function messages()
    {
        return \App\Rules\VaccinationRecordRules::messages();
    }

    public function save()
    {
        $this->validate(\App\Rules\VaccinationRecordRules::rules($this->cannot_vaccinate, $this->editId));
        try {
            $data = $this->only(['pet_id','customer_id', 'vaccination_id', 'date', 'notes','cannot_vaccinate']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }
            
            $data['document'] = $this->document;
            // $data['cannot_vaccinate'] = $this->cannot_vaccinate;

            $result = $this->vaccinationRecordService->saveVaccinationRecord($this->editId,$data);

            if ($result['status'] === 'success') {
                // Store record ID for address creation
                // $this->editId = $result['vaccination_record_id'];
                session()->flash('success', $result['message']);

                if($this->cannot_vaccinate == true)
                {
                    return redirect()->route($this->firstSegment.'.blood-test-records', ['id' => $this->pet_id,'customer_id' => $this->customer_id]);
                }
                
                $this->resetFields();
            } else {
                // Handle validation errors
                if (isset($result['errors'])) {
                    foreach ($result['errors'] as $field => $messages) {
                        foreach ($messages as $message) {
                            $this->addError($field, $message);
                        }
                    }
                } else {
                    session()->flash('error', $result['message']);
                }
            }
            
        } catch (Exception $e) {
            dd($e);
        }
    }

    public function edit($id)
    {
        try{
            $vaccinationRecord = VaccinationRecord::findOrFail($id);
            $this->editId = $id;
            $this->vaccination_id = $vaccinationRecord->vaccination_id;
            $this->date = $vaccinationRecord->date->format('Y-m-d');
            $this->notes = $vaccinationRecord->notes;
            $this->cannot_vaccinate = $vaccinationRecord->cannot_vaccinate;
            $this->src = $vaccinationRecord->document;
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function deletePopUp($id)
    {
        $this->deleteId = $id;
        $this->popUp = true;
    }

    public function delete()
    {
        try{
            $result = $this->vaccinationRecordService->deleteVaccinationRecord($this->deleteId);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
            $this->reset('deleteId', 'popUp');
            session()->flash('success', 'Vaccination Record deleted successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }
    
    /**
     * Generate and stream/download vaccination PDF for given record ID.
     */
    public function download(int $id)
    {
        try {
            $result = $this->vaccinationRecordService->downloadVaccinationRecord($id);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
                $pdfBytes = $result['pdfBytes'];

                return response()->stream(function() use ($pdfBytes) {
                    echo $pdfBytes;
                }, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline,attachment; filename="vaccination_record.pdf"',
                ]);
            } else {
                session()->flash('error', $result['message']);
            }

        } catch (Exception $e) {
            Log::error('Failed to generate vaccination PDF', ['error' => $e->getMessage()]);
            session()->flash('error', 'PDF generation failed.');
            // return response('PDF generation failed.', 500)
            //     ->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

    public function resetFields()
    {
        $this->reset(['vaccination_id', 'date', 'notes','cannot_vaccinate','document','src','editId']);
    }

}
