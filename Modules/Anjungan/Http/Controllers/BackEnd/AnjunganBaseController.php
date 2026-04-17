<?php

use App\Models\Galery;
use App\Models\Kategori;
use App\Models\SettingAplikasi;

defined('BASEPATH') || exit('No direct script access allowed');

class AnjunganBaseController extends AdminModulController
{
    public $moduleName          = 'Anjungan';
    public $modul_ini           = 'anjungan';
    public $kategori_pengaturan = 'Anjungan';

    protected function pengaturanAnjungan(): array
    {
        return SettingAplikasi::whereIn('key', [
            'sebutan_anjungan_mandiri',
            'anjungan_teks_berjalan',
            'anjungan_layar',
            'anjungan_profil',
            'anjungan_slide',
            'anjungan_video',
            'anjungan_youtube',
            'anjungan_artikel',
            'tampilan_anjungan',
            'tampilan_anjungan_waktu',
            'tampilan_anjungan_slider',
            'tampilan_anjungan_video',
            'warna_anjungan',
            'pencahayaan_anjungan',
        ])->pluck('value', 'key')->toArray();
    }

    protected function daftarKategori()
    {
        return Kategori::query()->orderBy('kategori')->get();
    }

    protected function daftarSlide()
    {
        return Galery::where('parrent', 0)->where('enabled', 1)->orderBy('nama')->get();
    }
}
