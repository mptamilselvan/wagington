<?php

namespace App\Rules;
use Illuminate\Validation\Rule;


class RoomRules
{
    public static function rules($editId = null)
    {

        return [
            'name'   => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($editId) {
                    $query = \App\Models\RoomModel::whereRaw('LOWER(name) = LOWER(?)', [$value]);
                    
                    if ($editId) {
                        $query->where('id', '!=', $editId);
                    }
                    
                    if ($query->exists()) {
                        $fail('This room name already exists (case-insensitive).');
                    }
                },
            ],
            'room_type_id'   => [
                'required',
                'integer',
                'min:0',
                'max:100',
            ],
            'cctv_stream'   => [
                'nullable',
                'string',
            ],
            'status'   => [
                'required',
                'string',
                Rule::in(array_keys(config('room.status_options'))),
            ],
        ];
    }

    public static function messages()
    {
        return [
            'name.required' => 'Room name is required.',
            'name.unique' => 'This room name already exists (case-insensitive).',
            'room_type_id.required' => 'Room type is required.',
            'room_type_id.integer' => 'Room type must be an integer.',
            'room_type_id.exists' => 'Room type must exist.',
            'cctv_stream.nullable' => 'CCTV stream is nullable.',
            'cctv_stream.string' => 'CCTV stream must be a string.',
            'status.required' => 'Status is required.',
            'status.string' => 'Status must be a string.',
            'status.in' => 'Status must be one of the valid options.'
           
        ];
    }
}

