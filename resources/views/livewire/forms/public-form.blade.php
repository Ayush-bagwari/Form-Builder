<div class="py-10 max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Main Container Card --}}
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">

        @if($submitted)
            {{-- Thank You Screen --}}
            <div class="p-10 text-center space-y-6">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl font-bold">
                    &check;
                </div>
                <h1 class="text-2xl font-extrabold text-gray-900">Submission Received!</h1>
                <p class="text-base text-gray-600 max-w-md mx-auto">
                    {{ $form->settings['success_message'] ?? 'Thank you! Your response has been recorded successfully.' }}
                </p>
                <div class="pt-4">
                    <button 
                        wire:click="$set('submitted', false)"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm transition"
                    >
                        Submit Another Response
                    </button>
                </div>
            </div>
        @else
            {{-- Form Header Banner --}}
            <div class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-700 p-8 text-white">
                <h1 class="text-3xl font-black tracking-tight mb-2">{{ $form->title }}</h1>
                @if(!empty($form->description))
                    <p class="text-indigo-100 text-sm leading-relaxed max-w-2xl">{{ $form->description }}</p>
                @endif
            </div>

            {{-- Form Fields Body --}}
            <form wire:submit.prevent="submit" class="p-8 space-y-8">

                @foreach($form->schema['sections'] ?? [] as $sIdx => $section)
                    <div class="space-y-6 border-b border-gray-100 pb-8 last:border-b-0 last:pb-0">
                        @if(!empty($section['title']))
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 border-l-4 border-indigo-600 pl-3">{{ $section['title'] }}</h2>
                                @if(!empty($section['description']))
                                    <p class="text-xs text-gray-500 mt-1 pl-4">{{ $section['description'] }}</p>
                                @endif
                            </div>
                        @endif

                        <div class="space-y-5">
                            @foreach($section['fields'] ?? [] as $field)
                                @php
                                    $fieldKey = 'answers.' . $field['key'];
                                @endphp

                                @if($field['type'] === 'section_heading')
                                    <div class="pt-4 border-t border-gray-200">
                                        <h3 class="text-base font-bold text-gray-800">{{ $field['label'] }}</h3>
                                        @if(!empty($field['help_text']))
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $field['help_text'] }}</p>
                                        @endif
                                    </div>
                                @else
                                    <div class="space-y-1.5">
                                        <label class="block text-sm font-semibold text-gray-800">
                                            {{ $field['label'] }}
                                            @if(!empty($field['required']))
                                                <span class="text-rose-500 font-bold">*</span>
                                            @endif
                                        </label>

                                        @if(!empty($field['help_text']))
                                            <p class="text-xs text-gray-500 mb-1">{{ $field['help_text'] }}</p>
                                        @endif

                                        {{-- Render Input Control --}}
                                        @switch($field['type'])

                                            {{-- Textarea --}}
                                            @case('textarea')
                                                <textarea 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    rows="3"
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                                ></textarea>
                                                @break

                                            {{-- Dropdown --}}
                                            @case('dropdown')
                                                <select 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                                >
                                                    <option value="">{{ !empty($field['placeholder']) ? $field['placeholder'] : '-- Select --' }}</option>
                                                    @foreach($field['options'] ?? [] as $opt)
                                                        <option value="{{ $opt['value'] }}">{{ $opt['label'] }}</option>
                                                    @endforeach
                                                </select>
                                                @break

                                            {{-- Radio Buttons --}}
                                            @case('radio')
                                                <div class="space-y-2 pt-1">
                                                    @foreach($field['options'] ?? [] as $opt)
                                                        <label class="flex items-center space-x-3 cursor-pointer">
                                                            <input 
                                                                type="radio" 
                                                                wire:model="answers.{{ $field['key'] }}"
                                                                value="{{ $opt['value'] }}"
                                                                class="w-4 h-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                                            >
                                                            <span class="text-sm text-gray-700 font-medium">{{ $opt['label'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @break

                                            {{-- Checkboxes --}}
                                            @case('checkbox')
                                                <div class="space-y-2 pt-1">
                                                    @foreach($field['options'] ?? [] as $opt)
                                                        <label class="flex items-center space-x-3 cursor-pointer">
                                                            <input 
                                                                type="checkbox" 
                                                                wire:model="answers.{{ $field['key'] }}"
                                                                value="{{ $opt['value'] }}"
                                                                class="w-4 h-4 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500"
                                                            >
                                                            <span class="text-sm text-gray-700 font-medium">{{ $opt['label'] }}</span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                                @break

                                            {{-- Date Picker --}}
                                            @case('date')
                                                <input 
                                                    type="date" 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                                >
                                                @break

                                            {{-- Rating --}}
                                            @case('rating')
                                                <div class="flex items-center space-x-2 pt-1">
                                                    @for($r = 1; $r <= 5; $r++)
                                                        <label class="cursor-pointer">
                                                            <input 
                                                                type="radio" 
                                                                wire:model="answers.{{ $field['key'] }}"
                                                                value="{{ $r }}"
                                                                class="sr-only peer"
                                                            >
                                                            <span class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-semibold text-gray-600 peer-checked:bg-amber-500 peer-checked:text-white peer-checked:border-amber-500 hover:bg-gray-50 transition">
                                                                {{ $r }} &starf;
                                                            </span>
                                                        </label>
                                                    @endfor
                                                </div>
                                                @break

                                            {{-- File Upload --}}
                                            @case('file')
                                                <input 
                                                    type="file" 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                                                >
                                                @break

                                            {{-- Number Input --}}
                                            @case('number')
                                                <input 
                                                    type="number" 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                                >
                                                @break

                                            {{-- Email Input --}}
                                            @case('email')
                                                <input 
                                                    type="email" 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                                    placeholder="{{ !empty($field['placeholder']) ? $field['placeholder'] : 'user@example.com' }}"
                                                >
                                                @break

                                            {{-- Phone Input --}}
                                            @case('phone')
                                                <input 
                                                    type="tel" 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                                    placeholder="{{ !empty($field['placeholder']) ? $field['placeholder'] : '+1 (555) 000-0000' }}"
                                                >
                                                @break

                                            {{-- Default Single Line Text Input --}}
                                            @default
                                                <input 
                                                    type="text" 
                                                    wire:model="answers.{{ $field['key'] }}"
                                                    class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                                >
                                        @endswitch

                                        {{-- Server Validation Error --}}
                                        @error($fieldKey)
                                            <p class="text-xs text-rose-600 font-medium mt-1">&excl; {{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif

                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="pt-6">
                    <button 
                        type="submit"
                        class="w-full py-3.5 px-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-base rounded-2xl shadow-lg hover:shadow-xl transition transform active:scale-[0.99]"
                    >
                        {{ $form->settings['submit_button_text'] ?? 'Submit Response' }}
                    </button>
                </div>
            </form>
        @endif

    </div>

</div>
