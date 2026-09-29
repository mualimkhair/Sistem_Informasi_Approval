<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Forms\Components\SignaturePad;
use App\Rules\RequiresTransparentBackground;
use Illuminate\Support\HtmlString;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema as DbSchema;
use App\Models\User;
use App\Models\PengajuanCuti;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Dasar')
                    ->schema([
                        TextInput::make('nip')
                            ->label('NIP')
                            ->disabled()
                            ->required(),
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required(),
                        \Filament\Forms\Components\Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(\App\Models\User::JENIS_KELAMIN)
                            ->required(),
                        Textarea::make('alamat')
                            ->label('Alamat')
                            ->required(),
                        TextInput::make('nomor_telp')
                            ->label('Nomor Telepon / HP')
                            ->tel()
                            ->required(),
                    ])->columns(2),
                
                Section::make('Informasi Pekerjaan')
                    ->schema([
                        DatePicker::make('tanggal_masuk')
                            ->label('Tanggal Masuk')
                            ->required(),
                        TextInput::make('jabatan')
                            ->label('Jabatan')
                            ->required(),
                        Select::make('pangkat_gol')
                            ->label('Pangkat/Golongan')
                            ->options(\App\Models\User::PANGKAT_GOLONGAN)
                            ->searchable()
                            ->nullable()
                            ->rule(\Illuminate\Validation\Rule::in(array_keys(\App\Models\User::PANGKAT_GOLONGAN))),
                        Select::make('unit_kerja_id')
                            ->label('Unit Kerja')
                            ->options(\App\Models\UnitKerja::pluck('nama_unit', 'id'))
                            ->searchable()
                            ->visible(fn () => !auth()->user()->hasRole(['kasubag', 'pejabat_berwenang', 'super_admin', 'admin']))
                            ->required(fn () => !auth()->user()->hasRole(['kasubag', 'pejabat_berwenang', 'super_admin', 'admin'])),
                    ])->columns(2),
                
                Section::make('Tanda Tangan & Password')
                    ->schema([
                        Radio::make('signature_method')
                            ->label('Metode Tanda Tangan')
                            ->options([
                                'gambar' => 'Gambar Langsung',
                                'upload' => 'Upload File',
                            ])
                            ->default('gambar')
                            ->live(),
                        SignaturePad::make('signature_path')
                            ->label('Tanda Tangan Digital')
                            ->columnSpanFull()
                            ->required(fn ($get) => ($get('signature_method') ?? 'gambar') === 'gambar' && empty(auth()->user()?->signature_path))
                            ->visible(fn ($get) => ($get('signature_method') ?? 'gambar') === 'gambar'),
                        FileUpload::make('signature_upload')
                            ->label('Upload File Tanda Tangan')
                            ->disk('public')
                            ->directory('signatures')
                            ->acceptedFileTypes(['image/png'])
                            ->maxSize(2048)
                            ->rules([new RequiresTransparentBackground()])
                            ->helperText(new HtmlString('
                                <ul class="text-xs text-gray-500 list-disc list-inside mt-1 space-y-0.5">
                                    <li>Format file: PNG</li>
                                    <li>Background/latar belakang wajib transparan</li>
                                    <li>Ukuran maksimal: 2 MB</li>
                                </ul>
                            '))
                            ->validationMessages([
                                'required' => 'File tanda tangan wajib diunggah.',
                                'max' => 'Ukuran file tanda tangan maksimal 2 MB.',
                                'mimes' => 'Format file tanda tangan harus berupa gambar PNG.',
                                'mimetypes' => 'Format file tanda tangan harus berupa gambar PNG.',
                            ])
                            ->getUploadedFileUsing(static function (FileUpload $component, string $file, $storedFileNames): ?array {
                                $storage = $component->getDisk();
                                if (! $storage->exists($file)) {
                                    return null;
                                }

                                return [
                                    'name' => basename($file),
                                    'size' => $storage->size($file),
                                    'type' => $storage->mimeType($file),
                                    'url' => url('storage/' . $file),
                                ];
                            })
                            ->required(fn ($get) => $get('signature_method') === 'upload' && empty(auth()->user()?->signature_path))
                            ->visible(fn ($get) => $get('signature_method') === 'upload'),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])->columns(1),
            ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        if (!empty($data['signature_path'])) {
            $data['signature_upload'] = $data['signature_path'];
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $signatureMethod = $data['signature_method'] ?? 'gambar';
        $oldPath = auth()->user()?->signature_path;

        if ($signatureMethod === 'upload' && !empty($data['signature_upload'])) {
            $uploadedPath = is_array($data['signature_upload']) ? reset($data['signature_upload']) : $data['signature_upload'];
            if (!empty($uploadedPath)) {
                $data['signature_path'] = $uploadedPath;
                if ($oldPath && $oldPath !== $uploadedPath) {
                    $this->deleteOldSignatureIfUnreferenced($oldPath, auth()->id());
                }
            }
        } elseif (isset($data['signature_path']) && str_starts_with($data['signature_path'], 'data:image')) {
            $imageParts = explode(";base64,", $data['signature_path']);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1] ?? 'png';
            $imageBase64 = base64_decode($imageParts[1]);
            $fileName = 'signatures/' . uniqid() . '.' . $imageType;
            
            if (Storage::disk('public')->put($fileName, $imageBase64)) {
                $data['signature_path'] = $fileName;
                
                if ($oldPath && $oldPath !== $fileName) {
                    $this->deleteOldSignatureIfUnreferenced($oldPath, auth()->id());
                }
            } else {
                $data['signature_path'] = $oldPath;
                
                Notification::make()
                    ->title('Gagal menyimpan tanda tangan')
                    ->body('Terjadi kesalahan saat menyimpan file tanda tangan baru ke server.')
                    ->danger()
                    ->send();
            }
        } elseif (empty($data['signature_path']) && $oldPath) {
            $data['signature_path'] = $oldPath;
        }

        unset($data['signature_method'], $data['signature_upload']);

        return $data;
    }

    protected function deleteOldSignatureIfUnreferenced(?string $oldPath, ?int $currentUserId = null): void
    {
        if (empty($oldPath)) {
            return;
        }

        if (! Storage::disk('public')->exists($oldPath)) {
            return;
        }

        // Cek apakah masih digunakan oleh user lain
        $userQuery = User::where('signature_path', $oldPath);
        if ($currentUserId) {
            $userQuery->where('id', '!=', $currentUserId);
        }
        if ($userQuery->exists()) {
            return;
        }

        // Cek apakah masih dirujuk oleh pengajuan_cutis.admin_signature_path
        if (DbSchema::hasTable('pengajuan_cutis') && DbSchema::hasColumn('pengajuan_cutis', 'admin_signature_path')) {
            if (PengajuanCuti::where('admin_signature_path', $oldPath)->exists()) {
                return;
            }
        }

        // Cek apakah masih dirujuk oleh blangko_cutis.kabandara_signature_path
        if (DbSchema::hasTable('blangko_cutis') && DbSchema::hasColumn('blangko_cutis', 'kabandara_signature_path')) {
            if (\App\Models\BlangkoCuti::where('kabandara_signature_path', $oldPath)->exists()) {
                return;
            }
        }

        Storage::disk('public')->delete($oldPath);
    }
}
