<?php

namespace App\Traits;

use App\Models\DewormingRecord;
use Auth;

trait DewormingRecordTrait
{
    protected function messages()
    {
        return \App\Rules\DewormingRecordRules::messages();
    }
    public function save()
    {
        $this->validate(\App\Rules\DewormingRecordRules::rules($this->editId));
        try {
            $data = $this->only(['pet_id','customer_id', 'date', 'notes','brand_name']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $data['document'] = $this->document;

            $result = $this->DewormingRecordService->saveDewormingRecord($this->editId,$data);

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
            $DewormingRecord = DewormingRecord::findOrFail($id);
            $this->editId = $id;
            $this->date = $DewormingRecord->date->format('Y-m-d');
            $this->notes = $DewormingRecord->notes;
            $this->brand_name = $DewormingRecord->brand_name;
            $this->src = $DewormingRecord->document;
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
            $result = $this->DewormingRecordService->deleteDewormingRecord($this->deleteId);
            
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
            $result = $this->DewormingRecordService->downloadDewormingRecord($id);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
                $pdfBytes = $result['pdfBytes'];

                return response()->stream(function() use ($pdfBytes) {
                    echo $pdfBytes;
                }, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline,attachment; filename="deworming_record.pdf"',
                ]);
            } else {
                session()->flash('error', $result['message']);
            }

        } catch (Exception $e) {
            Log::error('Failed to generate deworming_record PDF', ['error' => $e->getMessage()]);
            session()->flash('error', 'PDF generation failed.');
        }
    }

    public function resetFields()
    {
        $this->reset(['date', 'notes','brand_name','document','src','editId']);
    }
}