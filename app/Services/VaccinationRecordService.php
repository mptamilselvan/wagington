<?php
namespace App\Services;

use App\Models\VaccinationRecord;
use Illuminate\Support\Facades\Storage;
use Auth;
use setasign\Fpdi\Fpdi;

class VaccinationRecordService
{
    // Status constants
    private const STATUS_SUCCESS = 'success';
    private const STATUS_ERROR = 'error';

   public function getVaccinationRecord($pet_id): array
   {
        try {
            $data = VaccinationRecord::with('vaccination')->where('pet_id','=',$pet_id)->where('vaccination_id','!=',null)->orderby('id','asc')->get();
            
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

    public function saveVaccinationRecord(int $editId = null, array $data): array
    {
        try {
            if ($editId) {
                if (!Auth::user()->hasRole('admin')) {
                    $VaccinationRecord = VaccinationRecord::where('customer_id',Auth::user()->id)->findOrFail($editId);
                }
                else{
                    $VaccinationRecord = VaccinationRecord::findOrFail($editId);
                }
                if(!$data['cannot_vaccinate'])
                {
                    // If new file uploaded, replace old one
                    if (!empty($data['document'])) {
                        // Delete old image if it exists
                        if (!empty($VaccinationRecord->document) && Storage::disk('do_spaces')->exists($VaccinationRecord->document)) {
                            Storage::disk('do_spaces')->delete($VaccinationRecord->document);
                        }
                    
                        // Upload new one
                        $path = $data['document']->store('vaccination_record', 'do_spaces');
                        $data['document'] = $path;
                    } else {
                        // Don't overwrite existing image_url
                        unset($data['document']);
                    }
                } else{
                        $data['document'] = null; 
                    }

                $VaccinationRecord->update($data);
            } 
            else {
                if(!$data['cannot_vaccinate'])
                {
                    // Handle file upload
                    if (!empty($data['document'])) {
                        $path = $data['document']->store('vaccination_record', 'do_spaces');
                        $data['document'] = $path;
                    }
                }
                else{
                    $data['document'] = null; 
                }

                $VaccinationRecord = VaccinationRecord::create($data);
            }

            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Vaccination Record saved successfully.',
                'vaccination_record' => $VaccinationRecord
            ];

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status' => "not_found",
                'message' => 'Vaccination record not found or does not belong to you'
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
                'message' => 'Failed to save Vaccination Record: ' . $e->getMessage()
            ];
        }
    }

    private function validateVaccinationRecordData(array $data, int $editId = null)
    {
        if (!empty($data['cannot_vaccinate'])) {
            // When pet cannot be vaccinated, file is not required
            $rules = [
                'pet_id'         => 'required|exists:pets,id',
                'customer_id'         => 'required|exists:users,id',
                'vaccination_id' => 'nullable',
                'date'           => 'nullable|date',
                'document'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'notes'          => 'nullable|string|max:200',
                'cannot_vaccinate' => 'boolean',
            ];
        } else {
            // Require file only for creation
            $rules = [
                'pet_id'         => 'required|exists:pets,id',
                'customer_id'         => 'required|exists:users,id',
                'vaccination_id' => 'required|exists:vaccinations,id',
                'date'           => 'required|date',
                'document'       => $editId ? 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048' : 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
                'notes'          => 'nullable|string|max:200',
                'cannot_vaccinate' => 'boolean',
            ];
        }

        $validator = \Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }


    public function deleteVaccinationRecord($deleteId): array
    {
        try {
            if (!Auth::user()->hasRole('admin')) {
                $VaccinationRecord = VaccinationRecord::where('customer_id',Auth::user()->id)->findOrFail($deleteId);
            }
            else{
                $VaccinationRecord = VaccinationRecord::findOrFail($deleteId);
            }
            
            Storage::disk('do_spaces')->delete($VaccinationRecord->document);

            $VaccinationRecord->delete();
            
            return [
                'status' => self::STATUS_SUCCESS,
                'message' => 'Vaccination Record deleted successfully'
            ];
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return [
                'status'  => 'error',
                'message' => 'Vaccination record not found or does not belong to you'
            ];
        } catch (Exception $e) {
            return [
                'status' => self::STATUS_ERROR,
                'message' => 'Failed to delete records'
            ];
        }
    }

    public function downloadVaccinationRecord($id): array
    {
        try {
            // Fetch the record
            $record = VaccinationRecord::with('vaccination')->findOrFail($id);

            $expiryDate = $record->date->copy()->addDays($record->vaccination->expiry_days);

            // Initialize FPDI (extends FPDF)
            $pdf = new Fpdi();
            $pdf->SetCreator('Laravel App');
            $pdf->SetAuthor('Wagginton');
            $pdf->SetTitle('Vaccination Record');
            $pdf->SetMargins(15, 15, 15);
            $pdf->AddPage();

            // Add vaccination record content using FPDF methods
            $pdf->SetFont('Helvetica', 'B', 16);
            $pdf->Cell(0, 10, 'Vaccination Record', 0, 1, 'C');
            $pdf->Ln(5);

            $pdf->SetFont('Helvetica', '', 12);
            $pdf->Cell(35, 8, 'Vaccine Name :');
            $pdf->Cell(0, 8,  $record->vaccination->name ?? 'N/A', 0, 1);
            $pdf->Cell(35, 8, 'Date of Vaccine :');
            $pdf->Cell(0, 8, $record->date->format('d-m-Y') ?? 'N/A', 0, 1);
            $pdf->Cell(35, 8, 'Date of Expiry :');
            $date_of_expiry = $record->date->addDays($record->vaccination->expiry_days)->format('d-m-Y');
            if($expiryDate->isPast())
            {
                $date_of_expiry .= ' (Expired)';
            }
            $pdf->Cell(0, 8, $date_of_expiry ?? 'N/A', 0, 1);
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
                'message' => 'Vaccination Record dwonload successfully',
                'pdfBytes' => $pdfBytes
            ];
        } catch (Exception $e) {
            Log::error('Failed to generate vaccination PDF', ['error' => $e->getMessage()]);
            return response('PDF generation failed.', 500)
                ->header('Content-Type', 'text/plain; charset=UTF-8');
        }
    }

}