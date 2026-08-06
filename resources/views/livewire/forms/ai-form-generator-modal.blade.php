<div>
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity" wire:click="closeModal"></div>

            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-gray-100">
                    
                    <!-- Header -->
                    <div class="bg-gradient-to-r from-indigo-600 via-purple-600 to-pink-600 px-6 py-5 text-white flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-white/10 backdrop-blur-md rounded-xl">
                                <svg class="w-6 h-6 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold">
                                    {{ $actionType === 'create' ? 'Generate Form with AI' : 'AI Form Assistant (Refine)' }}
                                </h3>
                                <p class="text-xs text-indigo-100">
                                    {{ $actionType === 'create' ? 'Describe your form in plain English and let AI build it.' : 'Tell AI how to edit or translate your existing form.' }}
                                </p>
                            </div>
                        </div>

                        <button wire:click="closeModal" class="text-white/80 hover:text-white p-1 rounded-lg transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <form wire:submit.prevent="submit" class="p-6 space-y-5">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">
                                {{ $actionType === 'create' ? 'Natural Language Prompt' : 'Modification Instruction' }}
                            </label>
                            <textarea 
                                wire:model="prompt"
                                rows="4"
                                class="w-full px-4 py-3 text-sm text-gray-900 border border-gray-200 rounded-2xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-gray-400 shadow-sm"
                                placeholder="{{ $actionType === 'create' ? 'e.g. Internship application with education history, key skills, and resume upload field...' : 'e.g. Add emergency contact section with name and phone number...' }}"
                                required
                            ></textarea>
                            @error('prompt')
                                <p class="text-xs text-rose-600 mt-1.5 font-medium">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Presets -->
                        <div>
                            <span class="block text-[11px] font-bold text-gray-400 uppercase tracking-wider mb-2">Try a Preset Prompt:</span>
                            <div class="flex flex-wrap gap-2">
                                @if($actionType === 'create')
                                    <button 
                                        type="button" 
                                        wire:click="selectPreset('Internship application with education history, key skills, and resume upload')"
                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-medium rounded-xl transition border border-indigo-100"
                                    >
                                        🎓 Internship Application
                                    </button>
                                    <button 
                                        type="button" 
                                        wire:click="selectPreset('Customer feedback survey with star rating, service options, and comments')"
                                        class="px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-700 text-xs font-medium rounded-xl transition border border-purple-100"
                                    >
                                        ⭐ Customer Feedback
                                    </button>
                                    <button 
                                        type="button" 
                                        wire:click="selectPreset('Event registration form with ticket type choice, dietary preferences, and contact info')"
                                        class="px-3 py-1.5 bg-pink-50 hover:bg-pink-100 text-pink-700 text-xs font-medium rounded-xl transition border border-pink-100"
                                    >
                                        🎟️ Event Registration
                                    </button>
                                @else
                                    <button 
                                        type="button" 
                                        wire:click="selectPreset('Add an emergency contact section with contact name and phone number')"
                                        class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-medium rounded-xl transition border border-indigo-100"
                                    >
                                        ➕ Add Emergency Contact
                                    </button>
                                    <button 
                                        type="button" 
                                        wire:click="selectPreset('Translate all section titles and field labels to Hindi')"
                                        class="px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 text-xs font-medium rounded-xl transition border border-amber-100"
                                    >
                                        🌐 Translate to Hindi
                                    </button>
                                    <button 
                                        type="button" 
                                        wire:click="selectPreset('Make all phone and contact fields strictly required')"
                                        class="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-medium rounded-xl transition border border-emerald-100"
                                    >
                                        ⚠️ Make Phone Required
                                    </button>
                                @endif
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="pt-4 border-t border-gray-100 flex items-center justify-end gap-3">
                            <button 
                                type="button" 
                                wire:click="closeModal" 
                                class="px-5 py-2.5 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-xl transition"
                            >
                                Cancel
                            </button>

                            <button 
                                type="submit" 
                                wire:loading.attr="disabled"
                                class="px-6 py-2.5 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center gap-2"
                            >
                                <span wire:loading.remove>
                                    {{ $actionType === 'create' ? '✨ Generate Form' : '🚀 Apply AI Changes' }}
                                </span>
                                <span wire:loading class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Queueing Job...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
