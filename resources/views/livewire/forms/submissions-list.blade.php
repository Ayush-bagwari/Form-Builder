<div class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-indigo-600 hover:underline">&larr; Back to Dashboard</a>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">
                Submissions: {{ $form->title }}
            </h1>
            <p class="text-xs text-gray-500">Total Responses: {{ $form->submissions()->count() }}</p>
        </div>

        <div class="flex items-center gap-3">
            <button 
                wire:click="downloadCsv"
                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-sm transition flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export CSV
            </button>
        </div>
    </div>

    {{-- Search Bar --}}
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm mb-6 flex items-center gap-4">
        <div class="flex-1 relative">
            <input 
                type="text" 
                wire:model.live.debounce.300ms="search"
                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-indigo-500"
                placeholder="Search response answers or IP addresses..."
            >
            <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>
    </div>

    {{-- Submissions Table --}}
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-[11px] font-bold text-gray-500 uppercase tracking-wider">
                        <th class="py-3.5 px-4"># ID</th>
                        <th class="py-3.5 px-4">Submitted At</th>
                        <th class="py-3.5 px-4">IP Address</th>
                        <th class="py-3.5 px-4">Preview Answers</th>
                        <th class="py-3.5 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-xs text-gray-700">
                    @forelse($submissions as $sub)
                        <tr class="hover:bg-gray-50/80 transition">
                            <td class="py-3 px-4 font-mono font-bold text-gray-900">#{{ $sub->id }}</td>
                            <td class="py-3 px-4 text-gray-600">{{ $sub->created_at->format('M d, Y H:i A') }}</td>
                            <td class="py-3 px-4 font-mono text-gray-500 text-[11px]">{{ $sub->ip_address ?? 'N/A' }}</td>
                            <td class="py-3 px-4 max-w-md truncate">
                                @php
                                    $data = $sub->data ?? [];
                                    $summaryParts = [];
                                    foreach($data as $k => $v) {
                                        if (is_array($v)) {
                                            $v = isset($v['original_name']) ? $v['original_name'] : implode(', ', $v);
                                        }
                                        $summaryParts[] = Str::limit((string)$v, 20);
                                        if (count($summaryParts) >= 3) break;
                                    }
                                @endphp
                                <span class="text-gray-600">{{ implode(' • ', $summaryParts) }}</span>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button 
                                    wire:click="viewSubmission({{ $sub->id }})"
                                    class="px-3 py-1 bg-indigo-50 text-indigo-700 font-semibold rounded-lg hover:bg-indigo-100 text-xs"
                                >
                                    View Full Response
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12 text-center text-gray-400 text-xs">
                                No submissions found matching your query.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($submissions->hasPages())
            <div class="p-4 border-t border-gray-100">
                {{ $submissions->links() }}
            </div>
        @endif
    </div>

    {{-- Detail View Modal --}}
    @if($selectedSubmission)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-3xl max-w-2xl w-full shadow-2xl overflow-hidden animate-fade-in">
                <div class="p-6 border-b border-gray-100 flex items-center justify-between bg-indigo-600 text-white">
                    <div>
                        <h3 class="text-lg font-bold">Submission #{{ $selectedSubmission->id }}</h3>
                        <p class="text-xs text-indigo-100">{{ $selectedSubmission->created_at->format('F d, Y \a\t h:i A') }}</p>
                    </div>
                    <button wire:click="closeModal" class="text-white hover:text-gray-200 text-2xl font-bold">
                        &times;
                    </button>
                </div>

                <div class="p-6 space-y-4 max-h-[500px] overflow-y-auto">
                    @foreach($form->schema['sections'] ?? [] as $sec)
                        @foreach($sec['fields'] ?? [] as $field)
                            @if($field['type'] === 'section_heading') @continue @endif
                            @php
                                $val = $selectedSubmission->data[$field['key']] ?? 'N/A';
                            @endphp
                            <div class="p-3 bg-gray-50 rounded-xl border border-gray-100">
                                <span class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-1">{{ $field['label'] }}</span>
                                @if(is_array($val) && isset($val['path']))
                                    <a href="{{ asset('storage/' . $val['path']) }}" target="_blank" class="text-indigo-600 font-semibold underline text-xs">
                                        &darr; Download {{ $val['original_name'] }}
                                    </a>
                                @elseif(is_array($val))
                                    <span class="text-sm font-semibold text-gray-900">{{ implode(', ', $val) }}</span>
                                @else
                                    <span class="text-sm font-semibold text-gray-900">{{ $val ?: 'N/A' }}</span>
                                @endif
                            </div>
                        @endforeach
                    @endforeach
                </div>

                <div class="p-4 bg-gray-50 border-t border-gray-100 text-right">
                    <button wire:click="closeModal" class="px-5 py-2 bg-gray-200 text-gray-800 text-xs font-bold rounded-xl hover:bg-gray-300">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
