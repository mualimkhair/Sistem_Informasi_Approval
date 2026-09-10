<?php

namespace App\Filament\Resources\BlangkoCutis;

use App\Filament\Resources\BlangkoCutis\Pages\ManageBlangkoCutis;
use App\Models\BlangkoCuti;
use App\Models\PengajuanCuti;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class BlangkoCutiResource extends Resource
{
    protected static ?string $model = BlangkoCuti::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Blangko Cuti';

    protected static ?string $modelLabel = 'Blangko Cuti';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user->hasRole(['super_admin', 'admin', 'pejabat_berwenang']);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('pengajuanCuti.user.nama')
                    ->label('Pegawai')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('pengajuanCuti.tanggal_mulai')
                    ->label('Tanggal Mulai')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('pengajuanCuti.tanggal_selesai')
                    ->label('Tanggal Selesai')
                    ->date('d M Y')
                    ->sortable(),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'menunggu',
                        'success' => 'disetujui',
                        'danger' => 'ditolak',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('approval')
                    ->label('Approval Kabandara')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (BlangkoCuti $record) => $record->status === 'menunggu' && auth()->user()->hasRole('pejabat_berwenang'))
                    ->form([
                        Select::make('status')
                            ->label('Keputusan')
                            ->options([
                                'disetujui' => 'Setujui',
                                'ditolak' => 'Tolak',
                            ])
                            ->required()
                            ->reactive(),
                        Textarea::make('alasan')
                            ->label('Catatan Tambahan')
                            ->required(fn ($get) => $get('status') === 'ditolak'),
                    ])
                    ->action(function (BlangkoCuti $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $user = auth()->user();
                            $record->update([
                                'status' => $data['status'],
                                'alasan' => $data['alasan'] ?? null,
                                'tanggal_keputusan' => now(),
                                'kabandara_id' => $user->id,
                                'kabandara_nama' => $user->nama,
                                'kabandara_nip' => $user->nip,
                                'kabandara_pangkat' => $user->pangkat,
                            ]);
                            
                            if ($data['status'] === 'disetujui') {
                                \App\Services\CutiService::generateAndSaveFinalDocuments($record);
                                $record->refresh();
                                
                                Notification::make()
                                    ->title('Pengajuan Cuti Telah Disetujui')
                                    ->body('Pengajuan cuti Anda telah selesai diproses dan telah disetujui oleh Kabandara. Dokumen final telah tersedia di sistem.')
                                    ->actions([
                                        \Filament\Actions\Action::make('cetak_blangko')
                                            ->label('Lihat / Download Blangko Cuti')
                                            ->url(route('cetak-blangko', $record->pengajuan_cuti_id))
                                            ->button()
                                            ->color('success')
                                            ->openUrlInNewTab(),
                                        \Filament\Actions\Action::make('cetak_surat')
                                            ->label('Lihat / Download Surat Izin Cuti')
                                            ->url(route('cetak-surat-izin-cuti', $record->pengajuan_cuti_id))
                                            ->button()
                                            ->openUrlInNewTab(),
                                    ])
                                    ->info()
                                    ->sendToDatabase($record->pengajuanCuti->user);
                            } else {
                                Notification::make()
                                    ->title('Blangko Cuti ' . ucfirst($data['status']))
                                    ->body('Blangko cuti Anda telah ' . $data['status'] . ' oleh Kabandara.')
                                    ->info()
                                    ->sendToDatabase($record->pengajuanCuti->user);
                            }
                        });
                        
                        Notification::make()->title('Keputusan berhasil disimpan.')->success()->send();
                    }),
                    
                Action::make('cetak_blangko')
                    ->label('Cetak Blangko')
                    ->icon('heroicon-o-printer')
                    ->color('primary')
                    ->url(fn (BlangkoCuti $record) => route('cetak-blangko', $record->pengajuan_cuti_id))
                    ->openUrlInNewTab()
                    ->visible(fn (BlangkoCuti $record) => $record->status === 'disetujui'),
            ])
            ->filters([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageBlangkoCutis::route('/'),
        ];
    }
}
