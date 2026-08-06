<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full {{ $step === 2 ? 'sm:max-w-4xl' : 'sm:max-w-xl' }} border border-gray-100">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-6 py-5 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/10 backdrop-blur-md rounded-xl">
                                <svg class="w-6 h-6 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">
                                    {{ $step === 1 ? 'Import Form from Document' : 'Preview & Field Mapping' }}
                                </h3>
                                <p class="text-xs text-emerald-100">
                                    {{ $step === 1 ? 'Upload a Word (.docx) or Excel (.xlsx) file to automatically convert it into an interactive form.' : 'Review detected fields, edit types, and fix unparseable blocks before committing.' }}
                                </p>
                            </div>
                        </div>

                        <button wire:click="closeModal" class="text-white/80 hover:text-white p-1 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- STEP 1: UPLOAD -->
                    @if($step === 1)
                        <form wire:submit.prevent="processUpload" class="p-6 space-y-5">
                            @if($errorMessage)
                                <div class="p-4 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-700 font-medium">
                                    <strong>Error:</strong> {{ $errorMessage }}
                                </div>
                            @endif

                            <div class="border-2 border-dashed border-gray-300 rounded-3xl p-8 text-center hover:border-emerald-500 transition bg-gray-50/50">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>

                                <label class="block text-sm font-bold text-gray-700 mb-1 cursor-pointer">
                                    <span class="text-emerald-600 underline">Click to upload</span> or drag and drop
                                </label>
                                <p class="text-xs text-gray-500">Supports Word (.docx) & Excel (.xlsx, .csv) files (Max 10MB)</p>

                                <input 
                                    type="file" 
                                    wire:model="documentFile" 
                                    class="hidden" 
                                    id="docFileInput"
                                    accept=".docx,.doc,.xlsx,.xls,.csv"
                                >

                                <div class="mt-4">
                                    <label for="docFileInput" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow cursor-pointer inline-block">
                                        Browse Files
                                    </label>
                                </div>

                                @if($documentFile)
                                    <div class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-medium flex items-center justify-center gap-2">
                                        📄 Selected: <strong>{{ $documentFile->getClientOriginalName() }}</strong>
                                    </div>
                                @endif
                            </div>

                            <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-2xl text-xs text-indigo-900 space-y-1">
                                <strong>💡 Hybrid Import Engine:</strong>
                                <p class="text-gray-600">Document headings become Sections, questions become Fields, and list items become Option choices. Unclear fields are enriched using AI heuristics.</p>
                            </div>

                            <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                                <button type="button" wire:click="closeModal" class="px-5 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl">Cancel</button>

                                <button 
                                    type="submit" 
                                    wire:loading.attr="disabled"
                                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow transition flex items-center gap-2"
                                >
                                    <span wire:loading.remove>Parse Document &rarr;</span>
                                    <span wire:loading class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Parsing Document...
                                    </span>
                                </button>
                            </div>
                        </form>
                    @endif

                    <!-- STEP 2: PREVIEW & FIELD MAPPING -->
                    @if($step === 2)
                        <div class="p-6 space-y-6 max-h-[75vh] overflow-y-auto">
                            <!-- Title Input -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">Form Title</label>
                                <input 
                                    type="text" 
                                    wire:model="title" 
                                    class="w-full px-4 py-2.5 text-sm font-bold text-gray-900 border border-gray-300 rounded-xl focus:ring-2 focus:ring-emerald-500"
                                >
                                @error('title')
                                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Unparseable Blocks Alert -->
                            @if(!empty($unparseableBlocks))
                                <div class="p-4 bg-amber-50 border border-amber-200 rounded-2xl text-xs space-y-2">
                                    <div class="font-bold text-amber-900 flex items-center gap-1.5">
                                        <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                        {{ count($unparseableBlocks) }} Unparseable Block(s) Detected
                                    </div>
                                    <p class="text-amber-700">The parser could not automatically convert the following text blocks into questions. You can convert them to fields manually below:</p>

                                    <div class="space-y-1.5 pt-1">
                                        @foreach($unparseableBlocks as $bIndex => $block)
                                            <div class="flex items-center justify-between p-2 bg-white rounded-xl border border-amber-200 text-gray-800">
                                                <span class="truncate pr-2 font-mono text-[11px]">{{ $block }}</span>
                                                <button 
                                                    type="button" 
                                                    wire:click="convertUnparseableToField({{ $bIndex }})"
                                                    class="px-2.5 py-1 bg-amber-600 text-white rounded-lg text-[10px] font-bold hover:bg-amber-700 shrink-0"
                                                >
                                                    + Convert to Field
                                                </button>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Sections & Field Mapping Table -->
                            <div class="space-y-6">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-gray-500">Detected Sections & Field Mappings</h4>

                                @foreach($sections as $sIndex => $sec)
                                    <div class="bg-gray-50/70 border border-gray-200 rounded-2xl p-5 space-y-4 shadow-sm">
                                        <div class="flex items-center justify-between border-b border-gray-200 pb-3">
                                            <input 
                                                type="text" 
                                                wire:model="sections.{{ $sIndex }}.title" 
                                                class="font-bold text-sm text-gray-900 bg-transparent border-b border-gray-300 focus:border-emerald-500 px-1 py-0.5"
                                                placeholder="Section Title"
                                            >
                                            <button 
                                                type="button" 
                                                wire:click="addFieldToSection({{ $sIndex }})"
                                                class="text-xs font-bold text-emerald-600 hover:text-emerald-700 flex items-center gap-1"
                                            >
                                                + Add Field
                                            </button>
                                        </div>

                                        <div class="space-y-3">
                                            @foreach($sec['fields'] as $fIndex => $field)
                                                <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-sm flex flex-col md:flex-row items-start md:items-center justify-between gap-3">
                                                    <!-- Field Label -->
                                                    <div class="flex-1 w-full">
                                                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Field Label</label>
                                                        <input 
                                                            type="text" 
                                                            wire:model="sections.{{ $sIndex }}.fields.{{ $fIndex }}.label"
                                                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-emerald-500 font-medium"
                                                        >
                                                    </div>

                                                    <!-- Field Type Selector -->
                                                    <div class="w-full md:w-40">
                                                        <label class="block text-[10px] font-bold uppercase text-gray-400 mb-1">Detected Type</label>
                                                        <select 
                                                            wire:model="sections.{{ $sIndex }}.fields.{{ $fIndex }}.type"
                                                            class="w-full px-3 py-1.5 text-xs border border-gray-300 rounded-lg focus:ring-1 focus:ring-emerald-500 bg-white font-semibold text-gray-700"
                                                        >
                                                            <option value="text">Text Input</option>
                                                            <option value="email">Email</option>
                                                            <option value="phone">Phone Number</option>
                                                            <option value="textarea">Textarea</option>
                                                            <option value="number">Number</option>
                                                            <option value="select">Dropdown (Select)</option>
                                                            <option value="radio">Radio Choice</option>
                                                            <option value="checkbox">Checkbox</option>
                                                            <option value="file">File Upload</option>
                                                            <option value="date">Date</option>
                                                            <option value="rating">Rating (Stars)</option>
                                                            <option value="signature">Signature</option>
                                                        </select>
                                                    </div>

                                                    <!-- Required Toggle -->
                                                    <div class="flex items-center gap-1.5 pt-4 md:pt-0">
                                                        <input 
                                                            type="checkbox" 
                                                            wire:model="sections.{{ $sIndex }}.fields.{{ $fIndex }}.required"
                                                            id="req_{{ $sIndex }}_{{ $fIndex }}"
                                                            class="rounded text-emerald-600 focus:ring-emerald-500"
                                                        >
                                                        <label for="req_{{ $sIndex }}_{{ $fIndex }}" class="text-xs font-semibold text-gray-600">Required</label>
                                                    </div>

                                                    <!-- Remove Button -->
                                                    <button 
                                                        type="button" 
                                                        wire:click="removeField({{ $sIndex }}, {{ $fIndex }})"
                                                        class="p-1.5 text-gray-400 hover:text-rose-600 transition"
                                                        title="Delete Field"
                                                    >
                                                        &times;
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="pt-4 border-t border-gray-100 flex items-center justify-between">
                                <button type="button" wire:click="$set('step', 1)" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-xl text-xs font-semibold hover:bg-gray-50">
                                    &larr; Re-upload File
                                </button>

                                <button 
                                    type="button" 
                                    wire:click="commitImport"
                                    class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow transition"
                                >
                                    Create Form  &rarr;
                                </button>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    @endif
</div>
