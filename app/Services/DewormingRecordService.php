<?php
namespace App\Services;

use App\Models\DewormingRecord;
use Illuminate\Support\Facades\Storage;
use Auth;
use setasign\Fpdi\Fpdi;

class DewormingRecordService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getDewormingRecord($pet_id): array
   {
        try {
            $data = DewormingRecord::where('pet_id','=',$pet_id)->orderby('id','asc')->get();
            
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

    public function saveDewormingRecord(int $editId = null, array $data): array
    {
        try {
            // Validate first
            $validatedData = $this->validateDewormingRecordData($data, $editId);
            // dd($validatedData);

            if ($editId) {
                if (!Auth::user()->hasRole('admin')) {
                    $DewormingRecord = DewormingRecord::where('customer_id',Auth::user()->id)->findOrFail($editId);
                }
                else{
                    $DewormingRecord = DewormingRecord::findOrFail($editId);
                }
                
                // If new file uploaded, replace old one
                if (!empty($data['document'])) {
                    // Delete old image if it exists
                    if (!empty($DewormingRecord->document) && Storage::disk('do_spaces')->exists($DewormingRecord->document)) {
                        Storage::disk('do_spaces')->delete($DewormingRecord->document);
                    }
                 
                    // Upload new one
                    $path = $data['document']->store('deworming_record', 'do_spaces');
                    $data['document'] = $path;
                } else {
                    // Don't overwrite existing image_url
                    unset($data['document']);
                }

                $DewormingRecord->update($data);
            } 
            else {
                //  Creating new record
                if (!empty($data['document'])) {
                    $path = $data['document']->store('deworming_record', 'do_spaces');
                    $data['document'] = $path;
                }
            
                $DewormingRecord = DewormingRecord::create($data);
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Deworming Record saved successfully.',
                'deworming_record' => $DewormingRecord
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Deworming Record not found or does not belong to you'
            ];
        }  catch (\Illuminate\Validation\ValidationException $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to save Deworming Record Record: ' . $e->getMessage()
            ];
        }
    }

    private function validateDewormingRecordData(array $data, int $editId = null)
    {
        $rules = [
            'pet_id'         => 'required|exists:pets,id',
            'date'           => 'required|date',
            'document'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'notes'          => 'nullable|string|max:200',
            'brand_name' => 'required',
        ];

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }


    public function deleteDewormingRecord($deleteId): array
    {
        try {

            if (!Auth::user()->hasRole('admin')) {
                $DewormingRecord = DewormingRecord::where('customer_id',Auth::user()->id)->findOrFail($deleteId);
            }
            else{
                $DewormingRecord = DewormingRecord::findOrFail($deleteId);
            }

            Storage::disk('do_spaces')->delete($DewormingRecord->document);

            $DewormingRecord->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Deworming Record deleted successfully'
            ];
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Deworming Record not found or does not belong to you'
            ];
        } catch (\Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => $e->getMessage()
            ];
        }
    }

    public function downloadDewormingRecord($id)
    {
        try {
            // Fetch the record
            $record = DewormingRecord::findOrFail($id);

            // Initialize FPDI (extends FPDF)
            $pdf = new Fpdi();
            $pdf->SetCreator('Laravel App');
            $pdf->SetAuthor('Wagginton');
            $pdf->SetTitle('Deworming & Parasite Treatment');
            $pdf->SetMargins(15, 15, 15);
            $pdf->AddPage();

            // Add Deworming & Parasite Treatment content using FPDF methods
            $pdf->SetFont('Helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Deworming & Parasite Treatment', 0, 1, 'C');
            $pdf->Ln(5);

            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(35, 8, 'Brand name :');
            $pdf->Cell(0, 8,  $record->brand_name ?? 'N/A', 0, 1);
            $pdf->Cell(35, 8, 'Date of test :');
            $pdf->Cell(0, 8, $record->date->format('d-m-Y') ?? 'N/A', 0, 1);
            
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
                // $pdf->Image($imagePath, 15, 20, 100); // x=15, y=20, width=100mm
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
                'message' => 'Deworming Record dwonload successfully',
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