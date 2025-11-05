<div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ open: true }">
    <div class="px-6 py-4 border-b bg-gray-200 border-gray-200 flex justify-between items-center cursor-pointer" @click="open = !open">
        <h3 class="text-lg font-medium text-gray-900">SEO Settings</h3>
        <svg class="w-5 h-5 text-gray-500 transform transition-transform" :class="{ 'rotate-180': !open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </div>
    
    <div x-show="open" class="px-6 py-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <!-- Meta Title -->
                @component('components.textbox-component', [
                    'wireModel' => 'meta_title',
                    'id' => 'meta_title',
                    'label' => 'meta title',
                    'star' => true,      
                    'placeholder' => 'Enter meta title',              
                    'error' => $errors->first('meta_title'),
                ])
                @endcomponent

                @component('components.textbox-component', [
                    'wireModel' => 'meta_keywords',
                    'id' => 'meta_keywords',
                    'label' => 'meta keywords',
                    'star' => true,      
                    'placeholder' => 'Enter meta keywords',              
                    'error' => $errors->first('meta_keywords'),
                ])
                @endcomponent

                @component('components.textbox-component', [
                    'wireModel' => 'focus_keywords',
                    'id' => 'focus_keywords',
                    'label' => 'focus keywords',
                    'star' => true,      
                    'placeholder' => 'Enter focus keywords',              
                    'error' => $errors->first('focus_keywords'),
                ])
                @endcomponent
            </div>

            <div>
                @component('components.textarea-component', [
                    'wireModel' => 'meta_description',
                    'id' => 'meta_description',
                    'rows' => 8,
                    'label' => 'meta_description',
                    'placeholder' => 'Type here...',
                    'star' =>false,
                    'error' => $errors->first('meta_description'),
                    ])
                @endcomponent
            </div>
        </div>
    </div>
</div>