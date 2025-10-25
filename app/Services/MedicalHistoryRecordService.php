<?php
namespace App\Services;

use App\Models\MedicalHistoryRecord;
use Illuminate\Support\Facades\Storage;
use Auth;
use setasign\Fpdi\Fpdi;

class MedicalHistoryRecordService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getMedicalHistoryRecord($pet_id): array
   {
        try {
            $data = MedicalHistoryRecord::where('pet_id','=',$pet_id)->orderby('id','asc')->get();
            
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

    public function saveMedicalHistoryRecord(int $editId = null, array $data): array
    {
        try {

            if ($editId) {
                // If new file uploaded, replace old one
                if (!Auth::user()->hasRole('admin')) {
                    $MedicalHistoryRecord = MedicalHistoryRecord::where('customer_id',Auth::user()->id)->findOrFail($editId);
                }
                else{
                    $MedicalHistoryRecord = MedicalHistoryRecord::findOrFail($editId);
                }
                
                if (!empty($data['document'])) {
                    // Delete old image if it exists
                    if (!empty($MedicalHistoryRecord->document) && Storage::disk('do_spaces')->exists($MedicalHistoryRecord->document)) {
                        Storage::disk('do_spaces')->delete($MedicalHistoryRecord->document);
                    }
                 
                    // Upload new one
                    $path = $data['document']->store('medical_history_record', 'do_spaces');
                    $data['document'] = $path;
                } else {
                    // Don't overwrite existing image_url
                    unset($data['document']);
                }

                $MedicalHistoryRecord->update($data);
            } 
            else {
                //  Creating new record
                if (!empty($data['document'])) {
                    $path = $data['document']->store('medical_history_record', 'do_spaces');
                    $data['document'] = $path;
                }

                $MedicalHistoryRecord = MedicalHistoryRecord::create($data);
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Medical History Record saved successfully.',
                'medical_history_record' => $MedicalHistoryRecord
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Medical History Record not found or does not belong to you'
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
                'message' => 'Failed to save Medical History Record: ' . $e->getMessage()
            ];
        }
    }

    public function deleteMedicalHistoryRecord($deleteId): array
    {
        try {
            if (!Auth::user()->hasRole('admin')) {
                $MedicalHistoryRecord = MedicalHistoryRecord::where('customer_id',Auth::user()->id)->findOrFail($deleteId);
            }
            else{
                $MedicalHistoryRecord = MedicalHistoryRecord::findOrFail($deleteId);
            }
            Storage::disk('do_spaces')->delete($MedicalHistoryRecord->document);
            $MedicalHistoryRecord->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Medical History Record deleted successfully'
            ];
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Medical History Record not found or does not belong to you'
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function downloadMedicalHistoryRecord($id)
    {
        try {
            $record = MedicalHistoryRecord::findOrFail($id);
            // print_r(($record));
            // die;

            // Initialize FPDI (extends FPDF)
            $pdf = new Fpdi();
            $pdf->SetCreator('Laravel App');
            $pdf->SetAuthor('Wagginton');
            $pdf->SetTitle('Medical History Record');
            $pdf->SetMargins(15, 15, 15);
            $pdf->AddPage();

            // Add Medical History record content using FPDF methods
            $pdf->SetFont('Helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Medical History Record', 0, 1, 'C');
            $pdf->Ln(5);

            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(20, 8, 'Name :');
            $pdf->Cell(0, 8,  $record->name ?? 'N/A', 0, 1);
            
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
                'message' => 'Medical History Record dwonload successfully',
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