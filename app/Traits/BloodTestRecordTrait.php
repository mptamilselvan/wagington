<?php

namespace App\Traits;

use Auth;
use App\Models\BloodTestRecord;

trait BloodTestRecordTrait
{
    protected function messages()
    {
        return \App\Rules\BloodTestRecordRules::messages();
    }
    public function save()
    {
        $this->validate(\App\Rules\BloodTestRecordRules::rules($this->editId));
        try {
            $data = $this->only(['pet_id','customer_id', 'blood_test_id', 'date', 'notes','status']);
            if ($this->editId) {
                $data['updated_by'] = Auth::id();
            }
            else
            {
                $data['updated_by'] = Auth::id();
                $data['created_by'] = Auth::id();
            }

            $data['document'] = $this->document;

            $result = $this->BloodTestRecordService->saveBloodTestRecord($this->editId,$data);

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
            $BloodTestRecord = BloodTestRecord::findOrFail($id);
            $this->editId = $id;
            $this->blood_test_id = $BloodTestRecord->blood_test_id;
            $this->date = $BloodTestRecord->date->format('Y-m-d');
            $this->notes = $BloodTestRecord->notes;
            $this->status = strtolower($BloodTestRecord->status);
            $this->src = $BloodTestRecord->document;
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
            $result = $this->BloodTestRecordService->deleteBloodTestRecord($this->deleteId);
            
            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
            } else {
                session()->flash('error', $result['message']);
            }
            $this->reset('deleteId', 'popUp');
            session()->flash('success', 'Blood Test Record deleted successfully.');
        } catch (Exception $e) {
            $e->getMessage();
        }
    }

    public function download(int $id, string $filename = 'blood_test_record.pdf')
    {
        try {
           // Fetch the record
            $result = $this->BloodTestRecordService->downloadBloodTestRecord($id);

            if ($result['status'] === 'success') {
                session()->flash('success', $result['message']);
                $pdfBytes = $result['pdfBytes'];

                return response()->stream(function() use ($pdfBytes) {
                    echo $pdfBytes;
                }, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline,attachment; filename="blood_test_record.pdf"',
                ]);
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (Exception $e) {
            Log::error('Failed to generate blood_test PDF', ['error' => $e->getMessage()]);
            session()->flash('error', 'PDF generation failed.');
            // return response('PDF generation failed.', 500)
            //     ->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

    public function resetFields()
    {
        $this->reset(['blood_test_id', 'date', 'notes','status','document','src','editId']);
    }
}