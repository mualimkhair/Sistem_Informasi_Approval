<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use App\Forms\Components\SignaturePad;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Filament\Schemas\Components\Section;

class LengkapiProfil extends Page
{
    protected string $view = 'filament.pages.lengkapi-profil';

    public static function getNavigationIcon(): string | \BackedEnum | null
    {
        return 'heroicon-o-document-text';
    }

    public function getTitle(): string | \Illuminate\Contracts\Support\Htmlable
    {
        return 'Lengkapi Profil Anda';
    }

    public ?array $data = [];

    public static function shouldRegisterNavigation(): bool
    {
        return !auth()->user()->is_profile_completed;
    }

    public function mount(): void
    {
        $this->form->fill(auth()->user()->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form
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
                        Select::make('unit_kerja_id')
                            ->label('Unit Kerja')
                            ->options(\App\Models\UnitKerja::pluck('nama_unit', 'id'))
                            ->required(),
                    ])->columns(2),
                
                Section::make('Tanda Tangan & Password')
                    ->schema([
                        SignaturePad::make('signature_path')
                            ->label('Tanda Tangan Digital')
                            ->required(),
                        TextInput::make('password')
                            ->password()
                            ->label('Password Baru')
                            ->required(fn () => !auth()->user()->is_profile_completed)
                            ->minLength(8)
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state)),
                        TextInput::make('password_confirmation')
                            ->password()
                            ->label('Konfirmasi Password')
                            ->requiredWith('password')
                            ->same('password'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $signaturePath = $data['signature_path'];
        if (str_starts_with($signaturePath, 'data:image')) {
            $imageParts = explode(";base64,", $signaturePath);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1];
            $imageBase64 = base64_decode($imageParts[1]);
            $fileName = 'signatures/' . uniqid() . '.' . $imageType;
            \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageBase64);
            $signaturePath = $fileName;
        }

        auth()->user()->update([
            'nama' => $data['nama'],
            'alamat' => $data['alamat'],
            'tanggal_masuk' => $data['tanggal_masuk'],
            'jabatan' => $data['jabatan'],
            'unit_kerja_id' => $data['unit_kerja_id'],
            'nomor_telp' => $data['nomor_telp'],
            'signature_path' => $signaturePath,
            'is_profile_completed' => true,
        ]);

        if (!empty($data['password'])) {
            auth()->user()->update(['password' => $data['password']]);
        }

        Notification::make()
            ->title('Profil berhasil diperbarui')
            ->success()
            ->send();

        $this->redirect(route('filament.admin.pages.dashboard'));
    }
}
