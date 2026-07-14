<div class="space-y-6">
    {{-- Info pengajuan --}}
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div><strong>Pegawai:</strong> {{ $pengajuan->user->nama }}</div>
        <div><strong>Unit Kerja:</strong> {{ $pengajuan->user->unitKerja->nama_unit ?? '-' }}</div>
        <div><strong>Jenis:</strong> {{ str_replace('_', ' ', $pengajuan->jenis_cuti) }}</div>
        <div><strong>Lama:</strong> {{ $pengajuan->lama_cuti }} hari</div>
        <div><strong>Tanggal:</strong> {{ $pengajuan->tanggal_mulai?->format('d/m/Y') }} →
            {{ $pengajuan->tanggal_selesai?->format('d/m/Y') }}</div>
        <div><strong>Dibuat:</strong> {{ $pengajuan->created_at?->format('d/m/Y H:i') }}</div>
    </div>

    {{-- Status timeline --}}
    <div>
        <h3 class="text-lg font-semibold mb-2">Riwayat Status</h3>
        <div class="space-y-2">
            @forelse($pengajuan->statusLogs as $log)
                <div
                    class="flex items-start gap-3 p-2 rounded {{ $loop->first ? 'bg-blue-50' : ($loop->last ? 'bg-green-50' : '') }}">
                    <div
                        class="flex-shrink-0 w-2 h-2 mt-2 rounded-full {{ $loop->first ? 'bg-blue-500' : ($loop->last ? 'bg-green-500' : 'bg-gray-400') }}">
                    </div>
                    <div class="flex-1 text-sm">
                        <div>
                            @if($log->status_from)
                                <span class="badge">{{ str_replace('_', ' ', $log->status_from) }}</span>
                                →
                            @endif
                            <span class="badge">{{ str_replace('_', ' ', $log->status_to) }}</span>
                        </div>
                        <div class="text-gray-500 text-xs mt-1">
                            {{ $log->created_at->format('d/m/Y H:i') }}
                            @if($log->changedBy)
                                — oleh {{ $log->changedBy->nama }}
                            @endif
                        </div>
                        @if($log->keterangan)
                            <div class="text-gray-600 mt-0.5">{{ $log->keterangan }}</div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-gray-400 text-sm">Belum ada riwayat status.</div>
            @endforelse
        </div>
    </div>

    {{-- Ledger entries --}}
    <div>
        <h3 class="text-lg font-semibold mb-2">Mutasi Saldo (Ledger)</h3>
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100">
                    <th class="p-2 text-left">Aksi</th>
                    <th class="p-2 text-left">Jumlah</th>
                    <th class="p-2 text-left">Tanggal</th>
                    <th class="p-2 text-left">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengajuan->ledgers as $ledger)
                    <tr class="border-t">
                        <td class="p-2">
                            <span class="px-2 py-0.5 rounded text-xs font-medium
                                    {{ $ledger->aksi === 'hold' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $ledger->aksi === 'release' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $ledger->aksi === 'potong' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $ledger->aksi === 'rollover' ? 'bg-purple-100 text-purple-800' : '' }}
                                    {{ $ledger->aksi === 'factory_reset' ? 'bg-red-100 text-red-800' : '' }}">
                                {{ $ledger->aksi }}
                            </span>
                        </td>
                        <td class="p-2">{{ $ledger->jumlah }}</td>
                        <td class="p-2">{{ $ledger->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-2 text-gray-600">{{ $ledger->keterangan }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="p-2 text-gray-400">Tidak ada mutasi saldo.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>