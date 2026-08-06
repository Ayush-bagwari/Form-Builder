<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Top Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-lg flex items-center justify-between shadow-sm" role="alert">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium text-sm">{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">
                &times;
            </button>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mb-6 bg-rose-50 border border-rose-300 text-rose-800 px-4 py-3 rounded-lg flex items-center justify-between shadow-sm" role="alert">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5 text-rose-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium text-sm">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-700">
                &times;
            </button>
        </div>
    @endif

    {{-- Main Container Card --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        
        {{-- Header & Title --}}
        <div class="p-6 border-b border-gray-100 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Form Builder
                </h1>
            </div>

            <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                    <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $status === 'published' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                    {{ ucfirst($status) }}
                </span>

                @if($form && $form->exists)
                    <button 
                        type="button"
                        wire:click="$dispatch('openAiRefineModal', { formId: {{ $form->id }} })"
                        class="px-4 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-medium text-xs rounded-xl shadow-sm transition flex items-center gap-1.5"
                    >
                        <svg class="w-4 h-4 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        <span>AI Assistant</span>
                    </button>
                @endif

                <button 
                    type="button"
                    onclick="triggerFormBuilderSave()"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm hover:shadow transition flex items-center gap-2"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/>
                    </svg>
                    Save Form
                </button>
            </div>
        </div>

        {{-- Step Wizard Navigation Bar --}}
        <div class="bg-gray-50/70 border-b border-gray-200 px-6 py-4">
            <div class="max-w-3xl mx-auto flex items-center justify-between relative">
                {{-- Progress Connecting Line --}}
                <div class="absolute left-0 top-1/2 -translate-y-1/2 w-full h-0.5 bg-gray-200 -z-0"></div>
                
                {{-- Step 1: Details --}}
                <button 
                    wire:click="setStep(1)" 
                    class="relative z-10 flex items-center gap-2 px-4 py-2 rounded-full font-medium text-xs sm:text-sm transition {{ $currentStep === 1 ? 'bg-indigo-600 text-white shadow-md ring-4 ring-indigo-100' : ($currentStep > 1 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200') }}"
                >
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep === 1 ? 'bg-white text-indigo-600' : ($currentStep > 1 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-700') }}">
                        @if($currentStep > 1) &check; @else 1 @endif
                    </span>
                    <span>Details</span>
                </button>

                {{-- Step 2: Builder --}}
                <button 
                    wire:click="setStep(2)" 
                    onclick="setTimeout(() => window.initFormBuilderPlugin && window.initFormBuilderPlugin(true), 150)"
                    class="relative z-10 flex items-center gap-2 px-4 py-2 rounded-full font-medium text-xs sm:text-sm transition {{ $currentStep === 2 ? 'bg-indigo-600 text-white shadow-md ring-4 ring-indigo-100' : ($currentStep > 2 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200') }}"
                >
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep === 2 ? 'bg-white text-indigo-600' : ($currentStep > 2 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-700') }}">
                        @if($currentStep > 2) &check; @else 2 @endif
                    </span>
                    <span>Builder</span>
                </button>

                {{-- Step 3: Settings --}}
                <button 
                    wire:click="setStep(3)" 
                    class="relative z-10 flex items-center gap-2 px-4 py-2 rounded-full font-medium text-xs sm:text-sm transition {{ $currentStep === 3 ? 'bg-indigo-600 text-white shadow-md ring-4 ring-indigo-100' : ($currentStep > 3 ? 'bg-indigo-100 text-indigo-700' : 'bg-gray-100 text-gray-500 hover:bg-gray-200') }}"
                >
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep === 3 ? 'bg-white text-indigo-600' : ($currentStep > 3 ? 'bg-indigo-600 text-white' : 'bg-gray-300 text-gray-700') }}">
                        @if($currentStep > 3) &check; @else 3 @endif
                    </span> 
                    <span>Settings</span>
                </button>

                {{-- Step 4: Finish --}}
                <button 
                    wire:click="setStep(4)" 
                    class="relative z-10 flex items-center gap-2 px-4 py-2 rounded-full font-medium text-xs sm:text-sm transition {{ $currentStep === 4 ? 'bg-indigo-600 text-white shadow-md ring-4 ring-indigo-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                >
                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $currentStep === 4 ? 'bg-white text-indigo-600' : 'bg-gray-300 text-gray-700' }}">
                        4
                    </span>
                    <span>Finish</span>
                </button>
            </div>
        </div>

        {{-- STEP 1: DETAILS --}}
        @if($currentStep === 1)
            <div class="p-8 max-w-2xl mx-auto space-y-6">
                <div class="border-b border-gray-100 pb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Form basics</h2>
                        <p class="text-sm text-gray-500">Enter the primary details for your new data-collection form.</p>
                    </div>
                    <span class="px-3 py-1 bg-indigo-50 text-indigo-700 font-semibold text-xs rounded-full">
                        Survey Form
                    </span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="form-title" class="block text-sm font-semibold text-gray-700 mb-1">
                            Form title <span class="text-rose-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="form-title"
                            wire:model.live="title"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                            placeholder="e.g., Fall 2026 Registration"
                            maxlength="200"
                        >
                        @error('title')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-gray-400 mt-1 text-right">{{ strlen($title) }}/200</p>
                    </div>

                    <div>
                        <label for="form-desc" class="block text-sm font-semibold text-gray-700 mb-1">
                            Form Description / Subtitle
                        </label>
                        <textarea 
                            id="form-desc"
                            wire:model="description"
                            rows="3"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm shadow-sm"
                            placeholder="Provide background context or instructions for respondents..."
                        ></textarea>
                    </div>

                    <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 text-xs text-gray-600 space-y-1">
                        <span class="font-semibold text-gray-700">Public URL Preview:</span>
                        <p class="font-mono text-indigo-600 break-all">
                            {{ $form && $form->exists ? url('/f/' . $form->slug) : url('/f/[generated-slug-after-save]') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                    <a href="{{ route('dashboard') }}" class="px-5 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-xl text-sm transition">
                        Cancel
                    </a>

                    <button 
                        wire:click="setStep(2)"
                        onclick="setTimeout(() => window.initFormBuilderPlugin && window.initFormBuilderPlugin(true), 150)"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm transition flex items-center gap-1.5"
                    >
                        <span>Next: Builder</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 2: BUILDER (JQUERY FORMBUILDER PLUGIN & RAW JSON EDITOR) --}}
        @if($currentStep === 2)
            <div class="p-6 border-t border-gray-200">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <button 
                            type="button"
                            wire:click="switchView('formbuilder')"
                            onclick="setTimeout(() => window.initFormBuilderPlugin && window.initFormBuilderPlugin(true), 100)"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $activeView === 'formbuilder' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        >
                            Visual FormBuilder Canvas
                        </button>
                        <button 
                            type="button"
                            wire:click="switchView('raw-json')"
                            class="px-4 py-2 rounded-xl text-xs font-bold transition {{ $activeView === 'raw-json' ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                        >
                            Raw JSON Schema Editor
                        </button>
                    </div>

                    <button 
                        type="button"
                        onclick="triggerFormBuilderSave()"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-sm transition"
                    >
                        Sync & Save Canvas
                    </button>
                </div>

                {{-- View 1: jQuery formBuilder Canvas --}}
                <div class="{{ $activeView === 'formbuilder' ? 'block' : 'hidden' }}">
                    <div 
                        id="fb-editor" 
                        wire:ignore 
                        x-init="setTimeout(() => window.initFormBuilderPlugin && window.initFormBuilderPlugin(true), 100)"
                        class="min-h-[500px] p-4 bg-gray-50/50 rounded-2xl border border-gray-200 shadow-inner"
                    ></div>
                </div>

                {{-- View 2: Raw JSON Editor --}}
                @if($activeView === 'raw-json')
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold text-gray-700">Raw JSON Schema (Single Source of Truth)</h3>
                            <button 
                                type="button" 
                                wire:click="syncSchemaToRawJson" 
                                class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-2 py-1 rounded"
                            >
                                Format / Refresh
                            </button>
                        </div>

                        @if($jsonError)
                            <div class="p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium">
                                <strong>JSON Syntax Error:</strong> {{ $jsonError }}
                            </div>
                        @endif

                        <textarea 
                            wire:model.live.debounce.400ms="rawJson"
                            rows="20"
                            class="w-full font-mono text-xs p-4 bg-gray-900 text-emerald-400 rounded-2xl focus:ring-2 focus:ring-indigo-500 border border-gray-800 shadow-inner"
                            spellcheck="false"
                        ></textarea>
                    </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex items-center justify-between border-t border-gray-200 pt-6 mt-6">
                    <button 
                        wire:click="setStep(1)" 
                        class="px-5 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-xl text-sm transition"
                    >
                        &larr; Back
                    </button>

                    <button 
                        type="button"
                        @click="triggerFormBuilderSave(); $wire.setStep(3);"
                        class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm transition flex items-center gap-1.5"
                    >
                        <span>Next: Settings</span>
                        &rarr;
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 3: SETTINGS --}}
        @if($currentStep === 3)
            <div class="p-8 max-w-2xl mx-auto space-y-6">
                <div class="border-b border-gray-100 pb-4">
                    <h2 class="text-xl font-bold text-gray-900">Form Settings & Behaviors</h2>
                    <p class="text-sm text-gray-500">Configure submission responses, notifications, and status.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Form Status</label>
                        <select wire:model="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm">
                            <option value="published">Published (Publicly accessible)</option>
                            <option value="draft">Draft (Hidden from public)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Custom Submit Button Label</label>
                        <input type="text" wire:model="settings.submit_button_text" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="Submit Response">
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Success Message (after submission)</label>
                        <textarea wire:model="settings.success_message" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm" placeholder="Thank you for your submission!"></textarea>
                    </div>

                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                        <div>
                            <span class="block text-sm font-semibold text-gray-800">Spam Protection & Rate Limiting</span>
                            <span class="block text-xs text-gray-500">Limit multiple submissions from the same IP address</span>
                        </div>
                        <input type="checkbox" wire:model="settings.enable_rate_limit" class="rounded text-indigo-600 w-5 h-5">
                    </div>
                </div>

                <div class="flex items-center justify-between pt-6 border-t border-gray-100">
                    <button wire:click="setStep(2)" class="px-5 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-xl text-sm transition">
                        &larr; Back
                    </button>

                    <button wire:click="setStep(4)" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium text-sm rounded-xl shadow-sm transition flex items-center gap-1.5">
                        <span>Next: Finish</span>
                        &rarr;
                    </button>
                </div>
            </div>
        @endif

        {{-- STEP 4: FINISH & SHARE --}}
        @if($currentStep === 4)
            <div class="p-8 max-w-2xl mx-auto space-y-6 text-center">
                <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-2 text-2xl font-bold">
                    &check;
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Your Form is Ready!</h2>
                <p class="text-sm text-gray-500">Share your public link to start collecting submissions.</p>

                <div class="bg-gray-50 p-6 rounded-2xl border border-gray-200 text-left space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Public Fillable URL</label>
                        <div class="flex items-center gap-2">
                            <input 
                                type="text" 
                                readonly 
                                value="{{ $form && $form->exists ? url('/f/' . $form->slug) : 'Save form to generate public link' }}" 
                                id="publicUrlInput"
                                class="flex-1 px-3 py-2 bg-white border border-gray-300 rounded-xl text-xs font-mono"
                            >
                            <button 
                                type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('publicUrlInput').value); alert('Public link copied to clipboard!');"
                                class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-xs font-semibold hover:bg-indigo-700"
                            >
                                Copy Link
                            </button>
                        </div>
                    </div>

                    @if($form && $form->exists)
                        <div class="pt-4 border-t border-gray-200 flex items-center justify-around gap-4">
                            <a 
                                href="{{ route('forms.public', ['slug' => $form->slug]) }}" 
                                target="_blank"
                                class="px-5 py-2.5 bg-emerald-600 text-white hover:bg-emerald-700 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm"
                            >
                                View Form Live &rarr;
                            </a>

                            <a 
                                href="{{ route('forms.submissions', ['form' => $form->id]) }}" 
                                class="px-5 py-2.5 bg-gray-800 text-white hover:bg-gray-900 rounded-xl text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm"
                            >
                                View Submissions Dashboard
                            </a>
                        </div>
                    @endif
                </div>

                <div class="pt-6 border-t border-gray-100 flex items-center justify-between">
                    <button wire:click="setStep(3)" class="px-5 py-2 border border-gray-300 text-gray-700 hover:bg-gray-50 font-medium rounded-xl text-sm transition">
                        &larr; Back to Settings
                    </button>

                    <a href="{{ route('dashboard') }}" class="px-6 py-2.5 bg-indigo-600 text-white font-medium text-sm rounded-xl shadow-sm hover:bg-indigo-700 transition">
                        Go to Dashboard
                    </a>
                </div>
            </div>
        @endif

    </div>

    {{-- AI Generator Modal Component --}}
    @livewire('forms.ai-form-generator-modal')

</div>

@script
<script>
    let fbInstance = null;
    let isFbInitializing = false;

    function getInitialFields() {
        let rawData = $wire.schema;
        if (typeof rawData === 'string') {
            try { rawData = JSON.parse(rawData); } catch(e) {}
        }

        let fields = [];
        if (rawData && rawData.sections) {
            rawData.sections.forEach(sec => {
                if (sec.fields) {
                    sec.fields.forEach(f => {
                        let type = f.type;
                        let subtype = undefined;
                        if (type === 'dropdown') type = 'select';
                        if (type === 'radio') type = 'radio-group';
                        if (type === 'checkbox') type = 'checkbox-group';
                        if (type === 'rating') type = 'starRating';
                        if (type === 'section_heading') type = 'header';
                        if (type === 'phone') {
                            type = 'text';
                            subtype = 'tel';
                        }
                        if (type === 'email') {
                            type = 'text';
                            subtype = 'email';
                        }

                        fields.push({
                            type: type,
                            subtype: subtype,
                            label: f.label || 'Field',
                            name: f.key || ('field_' + Math.random().toString(36).substr(2, 6)),
                            placeholder: f.placeholder || '',
                            description: f.help_text || '',
                            required: !!f.required,
                            values: f.options ? f.options.map(o => ({ label: o.label, value: o.value })) : []
                        });
                    });
                }
            });
        }

        return fields;
    }

    window.initFormBuilderPlugin = function(forceReinit = false) {
        const container = document.getElementById('fb-editor');
        if (!container || !window.jQuery || !$.fn.formBuilder) return;

        // Prevent duplicate sidebar/canvas creation
        if (!forceReinit && container.querySelector('.build-wrap')) {
            return;
        }

        if (isFbInitializing) return;
        isFbInitializing = true;

        $(container).empty();

        const fieldsData = getInitialFields();

        try {
            fbInstance = $(container).formBuilder({
                formData: fieldsData,
                dataType: 'json',
                disableFields: [],
                i18n: {
                    location: 'https://cdnjs.cloudflare.com/ajax/libs/jQuery-formBuilder/3.8.3/lang'
                },
                onSave: function(evt, formData) {
                    $wire.saveFormFromJs(formData);
                }
            });
        } catch(e) {
            console.error('formBuilder error:', e);
        } finally {
            isFbInitializing = false;
        }
    };

    setTimeout(() => window.initFormBuilderPlugin(true), 150);

    document.addEventListener('livewire:navigated', () => {
        setTimeout(() => window.initFormBuilderPlugin && window.initFormBuilderPlugin(true), 150);
    });

    window.triggerFormBuilderSave = function() {
        if (fbInstance && fbInstance.actions) {
            const json = fbInstance.actions.getData('json');
            $wire.saveFormFromJs(json);
        } else {
            $wire.saveForm();
        }
    };
</script>
@endscript