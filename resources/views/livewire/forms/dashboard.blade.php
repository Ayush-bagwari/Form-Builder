<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" wire:poll.3s>

    {{-- Top Flash Messages --}}
    @if (session()->has('success'))
        <div class="mb-6 bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-xl flex items-center justify-between shadow-sm" role="alert">
            <span class="font-medium text-sm">{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    {{-- Header Banner --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-900 tracking-tight">Form Manager</h1>
            <p class="text-xs text-gray-500 mt-1">Build, manage, and view submissions for your interactive forms.</p>
        </div>

        <div class="flex items-center gap-3">
            <button 
                type="button"
                wire:click="$dispatch('openDocumentImportModal')"
                class="px-4 py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm rounded-2xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span>Import Document</span>
            </button>

            <button 
                type="button"
                wire:click="$dispatch('openAiModal')"
                class="px-5 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 font-bold text-sm rounded-2xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5 text-yellow-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
                <span>Generate with AI</span>
            </button>

            <a 
                href="{{ route('builder') }}" 
                class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-2xl shadow-md hover:shadow-lg transition flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Create New Form</span>
            </a>
        </div>
    </div>

    {{-- Search Filter --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm mb-6 flex items-center gap-4">
        <div class="flex-1 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search"
                class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500"
                placeholder="Search forms by title..."
            >
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    {{-- Forms Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($forms as $form)
            <div class="bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-md transition overflow-hidden flex flex-col justify-between group">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $form->status === 'published' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">
                                {{ $form->status }}
                            </span>
                            @if(in_array($form->ai_status, ['queued', 'processing']))
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-800 animate-pulse">
                                    <svg class="animate-spin w-3 h-3 text-purple-600" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    AI Generating...
                                </span>
                            @elseif($form->ai_status === 'failed')
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-100 text-rose-800">
                                    AI Failed
                                </span>
                            @endif
                        </div>
                        <span class="text-[11px] text-gray-400 font-mono">v{{ $form->version }}</span>
                    </div>

                    <h3 class="text-lg font-bold text-gray-900 group-hover:text-indigo-600 transition line-clamp-1">
                        {{ $form->title }}
                    </h3>
                    <p class="text-xs text-gray-500 mt-1 line-clamp-2 min-h-[32px]">
                        {{ $form->description ?: 'No description provided.' }}
                    </p>

                    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
                        <span class="flex items-center gap-1 font-semibold text-gray-700">
                            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ $form->submissions_count }} Submissions
                        </span>
                        <span>{{ $form->created_at->diffForHumans() }}</span>
                    </div>
                </div>

                {{-- Action Bar --}}
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <a 
                            href="{{ route('builder', ['form' => $form->id]) }}" 
                            class="px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 font-bold text-xs rounded-lg transition"
                        >
                            Edit
                        </a>

                        <a 
                            href="{{ route('forms.submissions', ['form' => $form->id]) }}" 
                            class="px-3 py-1.5 bg-gray-100 text-gray-700 hover:bg-gray-200 font-semibold text-xs rounded-lg transition"
                        >
                            Responses
                        </a>
                    </div>

                    <div class="flex items-center gap-1">
                        <button 
                            type="button"
                            wire:click="$dispatch('openAiRefineModal', { formId: {{ $form->id }} })"
                            class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg transition"
                            title="Refine Form with AI"
                        >
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </button>

                        <a 
                            href="{{ route('forms.public', ['slug' => $form->slug]) }}" 
                            target="_blank"
                            class="p-1.5 text-gray-400 hover:text-emerald-600 rounded-lg transition"
                            title="Public Form View"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </a>

                        <button 
                            wire:click="deleteForm({{ $form->id }})"
                            wire:confirm="Are you sure you want to delete this form and all its submissions?"
                            class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg transition"
                            title="Delete Form"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full py-16 text-center bg-white rounded-2xl border-2 border-dashed border-gray-200">
                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <h3 class="text-base font-bold text-gray-800">No forms created yet</h3>
                <p class="text-xs text-gray-500 mt-1 mb-4">Click below to create your first interactive form.</p>
                <a href="{{ route('builder') }}" class="px-5 py-2.5 bg-indigo-600 text-white font-bold text-xs rounded-xl shadow hover:bg-indigo-700">
                    + Create Form Now
                </a>
            </div>
        @endforelse
    </div>

    @if($forms->hasPages())
        <div class="mt-8">
            {{ $forms->links() }}
        </div>
    @endif

    {{-- AI Generator Modal Component --}}
    @livewire('forms.ai-form-generator-modal')

    {{-- Document Import Modal Component --}}
    @livewire('forms.document-import-modal')

</div>
