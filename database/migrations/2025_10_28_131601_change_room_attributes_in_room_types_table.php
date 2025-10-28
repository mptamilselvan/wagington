<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, validate and clean existing data to ensure it's valid JSON
        $this->validateAndCleanJsonData();
        
        // Use raw SQL to handle PostgreSQL casting from text to JSON
        DB::statement('ALTER TABLE room_types ALTER COLUMN room_attributes TYPE json USING room_attributes::json');
        DB::statement('ALTER TABLE room_types ALTER COLUMN room_amenities TYPE json USING room_amenities::json');
        
        // Make columns nullable
        DB::statement('ALTER TABLE room_types ALTER COLUMN room_attributes DROP NOT NULL');
        DB::statement('ALTER TABLE room_types ALTER COLUMN room_amenities DROP NOT NULL');
    }
    
    /**
     * Validate and clean existing data to ensure it's valid JSON
     */
    private function validateAndCleanJsonData(): void
    {
        // Get all records with non-null room_attributes or room_amenities
        $records = DB::table('room_types')
            ->whereNotNull('room_attributes')
            ->orWhereNotNull('room_amenities')
            ->get(['id', 'room_attributes', 'room_amenities']);
        
        foreach ($records as $record) {
            $updates = [];
            
            // Validate and clean room_attributes
            if ($record->room_attributes !== null) {
                $cleanedAttributes = $this->cleanJsonData($record->room_attributes);
                $updates['room_attributes'] = $cleanedAttributes;
            }
            
            // Validate and clean room_amenities
            if ($record->room_amenities !== null) {
                $cleanedAmenities = $this->cleanJsonData($record->room_amenities);
                $updates['room_amenities'] = $cleanedAmenities;
            }
            
            // Update the record if any changes were made
            if (!empty($updates)) {
                DB::table('room_types')
                    ->where('id', $record->id)
                    ->update($updates);
            }
        }
    }
    
    /**
     * Clean and validate JSON data
     */
    private function cleanJsonData(string $data): ?string
    {
        // If it's already valid JSON, return as is
        if (json_decode($data) !== null) {
            return $data;
        }
        
        // Try to fix common issues
        $cleaned = trim($data);
        
        // If it's empty or just whitespace, return null
        if (empty($cleaned)) {
            return null;
        }
        
        // If it looks like a JSON array or object, try to parse it
        if (preg_match('/^[\{\[].*[\}\]]$/', $cleaned)) {
            $decoded = json_decode($cleaned);
            if ($decoded !== null) {
                return json_encode($decoded);
            }
        }
        
        // For plain text, convert to a JSON object with a 'text' key
        // This preserves the original data while making it valid JSON
        return json_encode(['text' => $cleaned]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->longText('room_attributes')->nullable()->change();
            $table->longText('room_amenities')->nullable()->change();
        });
    }
};
