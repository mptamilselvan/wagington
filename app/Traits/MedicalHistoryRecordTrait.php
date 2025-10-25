<?php

namespace App\Traits;

use Auth;
use App\Models\MedicalHistoryRecord;

trait MedicalHistoryRecordTrait
{
    protected function messages()
    {
        return \App\Rules\MedicalHistoryRecordRules::messages();
    }
    public function save()
    {
        $this->validate(\App\Rules\MedicalHistoryRecordRules::rules($this->editId));
        try {
            $data = $this->only(['pet_id','customer_id', 'notes','name']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $data['document'] = $this->document;

            $result = $this->MedicalHistoryRecordService->saveMedicalHistoryRecord($this->editId,$data);

            if ($result['status'] === 'success') {
                // Store record ID for address creation
                session()->flash('success', $result['message']);
                
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
            $MedicalHistoryRecord = MedicalHistoryRecord::findOrFail($id);
            $this->editId = $id;
            $this->notes = $MedicalHistoryRecord->notes;
            $this->name = $MedicalHistoryRecord->name;
            $this->src = $MedicalHistoryRecord->document;
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
            $result = $this->MedicalHistoryRecordService->deleteMedicalHistoryRecord($this->deleteId);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
            $this->reset('deleteId', 'popUp');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function download(int $id)
    {
        try {
           // Fetch the record
            $result = $this->MedicalHistoryRecordService->downloadMedicalHistoryRecord($id);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
                $pdfBytes = $result['pdfBytes'];

                return response()->stream(function() use ($pdfBytes) {
                    echo $pdfBytes;
                }, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline,attachment; filename="medical_history_record.pdf"',
                ]);
            } else {
                session()->flash('error', $result['message']);
            }

        } catch (Exception $e) {
            Log::error('Failed to generate medical_history_record PDF', ['error' => $e->getMessage()]);
            session()->flash('error', 'PDF generation failed.');
        }
    }

    public function resetFields()
    {
        $this->reset(['notes','name','document','src','editId']);
    }
}