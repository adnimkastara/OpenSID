<?php

use App\Models\SettingAplikasi;

defined('BASEPATH') || exit('No direct script access allowed');

class AnjunganPengaturanController extends AnjunganBaseController
{
    public $sub_modul_ini   = 'anjungan_pengaturan';
    public $aliasController = 'anjungan_pengaturan';

    public function __construct()
    {
        parent::__construct();
        isCan('b');
    }

    public function index()
    {
        $pengaturan = $this->pengaturanAnjungan();

        return view('anjungan::backend.pengaturan.index', [
            'form_action'      => ci_route('anjungan_pengaturan.update'),
            'pengaturan'       => $pengaturan,
            'slides'           => $this->daftarSlide(),
            'daftar_kategori'  => $this->daftarKategori(),
            'anjungan_artikel' => json_decode((string) ($pengaturan['anjungan_artikel'] ?? '[]'), true) ?: [],
            'list_setting'     => SettingAplikasi::whereIn('key', ['warna_anjungan', 'pencahayaan_anjungan'])->get(),
        ]);
    }

    public function update(): void
    {
        isCan('u');

        $payload = [
            'sebutan_anjungan_mandiri' => trim((string) $this->input->post('sebutan_anjungan_mandiri')),
            'anjungan_layar'           => (int) $this->input->post('layar'),
            'anjungan_teks_berjalan'   => trim((string) $this->input->post('teks_berjalan')),
            'anjungan_profil'          => (int) $this->input->post('tampilan_profil'),
            'anjungan_slide'           => (int) $this->input->post('slide'),
            'anjungan_video'           => trim((string) $this->input->post('video')),
            'anjungan_youtube'         => trim((string) $this->input->post('youtube')),
            'anjungan_artikel'         => json_encode(array_map('intval', (array) $this->input->post('artikel'))),
            'tampilan_anjungan'        => (int) $this->input->post('screensaver'),
            'tampilan_anjungan_waktu'  => (int) $this->input->post('screensaver_waktu'),
            'tampilan_anjungan_slider' => (int) $this->input->post('screensaver_slide'),
            'tampilan_anjungan_video'  => trim((string) $this->input->post('screensaver_video')),
            'warna_anjungan'           => trim((string) $this->input->post('warna_anjungan')),
            'pencahayaan_anjungan'     => trim((string) $this->input->post('pencahayaan_anjungan')),
        ];

        foreach ($payload as $key => $value) {
            SettingAplikasi::query()->where('key', $key)->update(['value' => $value]);
        }

        redirect_with('success', 'Berhasil Ubah Pengaturan Anjungan');
    }
}
