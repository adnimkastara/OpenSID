<?php

use Illuminate\Support\Facades\View;
use Modules\Anjungan\Models\Anjungan;

defined('BASEPATH') || exit('No direct script access allowed');

class AnjunganController extends AnjunganBaseController
{
    public $sub_modul_ini   = 'anjungan';
    public $aliasController = 'anjungan';

    public function __construct()
    {
        parent::__construct();
        isCan('b');
    }

    public function index()
    {
        return view('anjungan::backend.anjungan.index');
    }

    public function datatables()
    {
        if (! $this->input->is_ajax_request()) {
            return show_404();
        }

        return datatables()->of(Anjungan::query())
            ->addIndexColumn()
            ->addColumn('ceklist', static function ($row) {
                if (can('h')) {
                    return '<input type="checkbox" name="id_cb[]" value="' . $row->id . '"/>';
                }
            })
            ->addColumn('aksi', static function ($row): string {
                $aksi = '';

                if (can('u')) {
                    $aksi .= View::make('admin.layouts.components.buttons.edit', [
                        'url' => 'anjungan/form/' . $row->id,
                    ])->render();
                }

                if (can('h')) {
                    $aksi .= View::make('admin.layouts.components.buttons.hapus', [
                        'url'           => ci_route('anjungan.delete', $row->id),
                        'confirmDelete' => true,
                    ])->render();
                }

                return $aksi;
            })
            ->addColumn('ip_address_port_printer', static fn ($row) => trim(($row->printer_ip ?: '-') . ' / ' . ($row->printer_port ?: '-')))
            ->editColumn('keyboard', static fn ($row) => $row->keyboard ? 'Aktif' : 'Tidak Aktif')
            ->editColumn('status', static function ($row): string {
                $class = $row->status ? 'success' : 'danger';
                $text  = $row->status ? 'Aktif' : 'Tidak Aktif';

                return '<span class="label label-' . $class . '">' . $text . '</span>';
            })
            ->editColumn('permohonan_surat_tanpa_akun', static fn ($row) => $row->permohonan_surat_tanpa_akun ? 'Aktif' : 'Tidak Aktif')
            ->rawColumns(['ceklist', 'aksi', 'status'])
            ->make();
    }

    public function form($id = null)
    {
        isCan('u');

        $anjungan = $id ? Anjungan::findOrFail($id) : new Anjungan();

        return view('anjungan::backend.anjungan.form', [
            'action'      => $id ? 'Ubah' : 'Tambah',
            'anjungan'    => $anjungan,
            'form_action' => $id ? ci_route('anjungan.update', $id) : ci_route('anjungan.insert'),
        ]);
    }

    public function insert(): void
    {
        isCan('u');

        $data = $this->payload();

        if (! Anjungan::create($data)) {
            redirect_with('error', 'Gagal Tambah Data');
        }

        redirect_with('success', 'Berhasil Tambah Data');
    }

    public function update($id): void
    {
        isCan('u');

        $anjungan = Anjungan::findOrFail($id);

        if (! $anjungan->update($this->payload())) {
            redirect_with('error', 'Gagal Ubah Data');
        }

        redirect_with('success', 'Berhasil Ubah Data');
    }

    public function delete($id = null): void
    {
        isCan('h');

        $ids = $id ? [$id] : (array) $this->input->post('id_cb');
        $ok  = Anjungan::whereIn('id', array_filter($ids))->delete();

        if (! $ok) {
            redirect_with('error', 'Gagal Hapus Data');
        }

        redirect_with('success', 'Berhasil Hapus Data');
    }

    public function delete_all(): void
    {
        $this->delete();
    }

    public function kunci($id = null, $val = null): void
    {
        isCan('u');

        $anjungan = Anjungan::findOrFail($id);
        $anjungan->status = (int) $val;
        $anjungan->save();

        redirect_with('success', 'Berhasil Ubah Status');
    }

    private function payload(): array
    {
        $data = [
            'ip_address'                  => strip_tags(trim((string) $this->input->post('ip_address'))),
            'mac_address'                 => strip_tags(trim((string) $this->input->post('mac_address'))),
            'id_pengunjung'               => strip_tags(trim((string) $this->input->post('id_pengunjung'))),
            'printer_ip'                  => strip_tags(trim((string) $this->input->post('printer_ip'))),
            'printer_port'                => strip_tags(trim((string) $this->input->post('printer_port'))),
            'keterangan'                  => strip_tags(trim((string) $this->input->post('keterangan'))),
            'keyboard'                    => (int) $this->input->post('keyboard') === 1 ? 1 : 0,
            'permohonan_surat_tanpa_akun' => (int) $this->input->post('permohonan_surat_tanpa_akun') === 1 ? 1 : 0,
        ];

        if ($data['ip_address'] === '' && $data['mac_address'] === '' && $data['id_pengunjung'] === '') {
            redirect_with('error', 'IP Address, Mac Address, atau ID Pengunjung wajib diisi salah satu.');
        }

        return $data;
    }
}
