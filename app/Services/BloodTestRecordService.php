<?php
namespace App\Services;

use App\Models\BloodTestRecord;
use Illuminate\Support\Facades\Storage;
use Auth;
use setasign\Fpdi\Fpdi;

class BloodTestRecordService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getBloodTestRecord($pet_id): array
   {
        try {
            $data = BloodTestRecord::with('blood_test')->where('pet_id','=',$pet_id)->orderby('id','asc')->get();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'data' => $data
            ];
            
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to get records'
            ];
        }
    }

    public function saveBloodTestRecord(int|string|null $editId, array $data): array
    {
        try {
            // Validate first
            // $validatedData = $this->validateBloodTestRecordData($data, $editId);

            if ($editId) {
                // If new file uploaded, replace old one
                if (!Auth::user()->hasRole('admin')) {
                    $BloodTestRecord = BloodTestRecord::where('customer_id',Auth::user()->id)->findOrFail($editId);
                }
                else{
                    $BloodTestRecord = BloodTestRecord::findOrFail($editId);
                }
                
                if (!empty($data['document'])) {
                    // Delete old image if it exists
                    if (!empty($BloodTestRecord->document) && Storage::disk('do_spaces')->exists($BloodTestRecord->document)) {
                        Storage::disk('do_spaces')->delete($BloodTestRecord->document);
                    }
                 
                    // Upload new one
                    $path = $data['document']->store('blood_test_record', 'do_spaces');
                    $data['document'] = $path;
                } else {
                    // Don't overwrite existing image_url
                    unset($data['document']);
                }

                $BloodTestRecord->update($data);
            } 
            else {
                //  Creating new record
                if (!empty($data['document'])) {
                    $path = $data['document']->store('blood_test_record', 'do_spaces');
                    $data['document'] = $path;
                }

                $BloodTestRecord = BloodTestRecord::create($data);
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Blood Test Record saved successfully.',
                'blood_test_record' => $BloodTestRecord
            ];

        }catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Blood test record not found or does not belong to you'
            ];
        } catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to save Blood Test Record: ' . $e->getMessage()
            ];
        }
    }

    private function validateBloodTestRecordData(array $data, int $editId = null)
    {
        $rules = [
            'pet_id'         => 'required|exists:pets,id',
            'blood_test_id' => 'required|exists:blood_tests,id',
            'date'           => 'required|date',
            'document'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes'          => 'nullable|string|max:200',
            'status' => 'required',
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }


    public function deleteBloodTestRecord($deleteId): array
    {
        try {
            
            if (!Auth::user()->hasRole('admin')) {
                $BloodTestRecord = BloodTestRecord::where('customer_id',Auth::user()->id)->findOrFail($deleteId);
            }
            else{
                $BloodTestRecord = BloodTestRecord::findOrFail($deleteId);
            }

            Storage::disk('do_spaces')->delete($BloodTestRecord->document);

            $BloodTestRecord->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Blood Test Record deleted successfully'
            ];
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Blood test record not found or does not belong to you'
            ];
        }  catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function downloadBloodTestRecord($id)
    {
        try {
            $record = BloodTestRecord::with('blood_test')->findOrFail($id);

            $expiryDate = $record->date->copy()->addDays($record->blood_test->expiry_days);

            // Initialize FPDI (extends FPDF)
            $pdf = new Fpdi();
            $pdf->SetCreator('Laravel App');
            $pdf->SetAuthor('Wagginton');
            $pdf->SetTitle('Blood Test Record');
            $pdf->SetMargins(15, 15, 15);
            $pdf->AddPage();

            // Add blood_test record content using FPDF methods
            $pdf->SetFont('Helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Blood Test Record', 0, 1, 'C');
            $pdf->Ln(5);

            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(35, 8, 'Test Name :');
            $pdf->Cell(0, 8,  $record->blood_test->name ?? 'N/A', 0, 1);
            $pdf->Cell(35, 8, 'Date of test :');
            $pdf->Cell(0, 8, $record->date->format('d-m-Y') ?? 'N/A', 0, 1);
            $pdf->Cell(35, 8, 'Date of Expiry :');
            $date_of_expiry = $record->date->addDays($record->blood_test->expiry_days)->format('d-m-Y');
            if($expiryDate->isPast())
            {
                $date_of_expiry .= ' (Expired)';
            }
            $pdf->Cell(0, 8, $date_of_expiry ?? 'N/A', 0, 1);
            $pdf->Cell(35, 8, 'Test status :');
            $pdf->Cell(0, 8, $record->status ?? 'N/A', 0, 1);

            if($record->notes)
            {
                $pdf->Ln(10); // add some spacing after date_of_expiry
                // MultiCell(width, height, text, border, align)
                $pdf->MultiCell(0, 8, $record->notes ?? 'N/A',0,'L');
                // Add image
                $pdf->Ln(5); // add some spacing after notes
            }

            $documentUrl = env('DO_SPACES_URL') . '/' . $record->document;

            // Get the file extension
            $extension = pathinfo($documentUrl, PATHINFO_EXTENSION);

            // Option 1: Check by extension
            if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
                $type = 'image';
            } elseif (strtolower($extension) === 'pdf') {
                $type = 'pdf';
            } else {
                $type = 'unknown';
            }

            if($type == 'image')
            {
                $yPosition = $pdf->GetY() + 10; 
                $pdf->Image($documentUrl, 15, $yPosition, 180);
                $pdf->Ln(60); // move cursor below image
            }
            else if($type == 'pdf')
            {
                $externalUrl = $documentUrl;
                $tempExternal = storage_path('app/external.pdf');
                file_put_contents($tempExternal, file_get_contents($externalUrl));

                $pageCount = $pdf->setSourceFile($tempExternal);
                for ($page = 1; $page <= $pageCount; $page++) {
                    $tplIdx = $pdf->importPage($page);
                    $size = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);
                }
            }

            // Output merged PDF directly for download
            $pdfBytes = $pdf->Output('S');

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Blood Test Record dwonload successfully',
                'pdfBytes' => $pdfBytes
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'PDF generation failed.'
            ];
        }
    }
}