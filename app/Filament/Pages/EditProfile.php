<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use App\Forms\Components\SignaturePad;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;

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
                            ->searchable()
                            ->required(),
                    ])->columns(2),
                
                Section::make('Tanda Tangan & Password')
                    ->schema([
                        SignaturePad::make('signature_path')
                            ->label('Tanda Tangan Digital')
                            ->columnSpanFull()
                            ->required(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ])->columns(1),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['signature_path']) && str_starts_with($data['signature_path'], 'data:image')) {
            $imageParts = explode(";base64,", $data['signature_path']);
            $imageTypeAux = explode("image/", $imageParts[0]);
            $imageType = $imageTypeAux[1];
            $imageBase64 = base64_decode($imageParts[1]);
            $fileName = 'signatures/' . uniqid() . '.' . $imageType;
            \Illuminate\Support\Facades\Storage::disk('public')->put($fileName, $imageBase64);
            $data['signature_path'] = $fileName;
        }

        return $data;
    }
}
