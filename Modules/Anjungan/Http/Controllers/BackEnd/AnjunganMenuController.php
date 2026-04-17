<?php

use App\Models\Artikel;
use App\Models\Kategori;
use App\Models\Kelompok;
use App\Models\Suplemen;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Modules\Anjungan\Models\AnjunganMenu;

defined('BASEPATH') || exit('No direct script access allowed');

class AnjunganMenuController extends AnjunganBaseController
{
    public $sub_modul_ini   = 'anjungan_menu';
    public $aliasController = 'anjungan_menu';

    public function __construct()
    {
        parent::__construct();
        isCan('b');
    }

    public function index()
    {
        return view('anjungan::backend.menu.index');
    }

    public function datatables()
    {
        if (! $this->input->is_ajax_request()) {
            return show_404();
        }

        return datatables()->of(AnjunganMenu::query()->orderBy('urut'))
            ->addIndexColumn()
            ->addColumn('drag-handle', static fn (): string => '<i class="fa fa-sort-alpha-desc"></i>')
            ->addColumn('ceklist', static function ($row) {
                if (can('h')) {
                    return '<input type="checkbox" name="id_cb[]" value="' . $row->id . '"/>';
                }
            })
            ->addColumn('aksi', static function ($row): string {
                $aksi = '';

                if (can('u')) {
                    $aksi .= View::make('admin.layouts.components.buttons.edit', [
                        'url' => 'anjungan_menu/form/' . $row->id,
                    ])->render();
                }

                if (can('h')) {
                    $aksi .= View::make('admin.layouts.components.buttons.hapus', [
                        'url'           => ci_route('anjungan_menu.delete', $row->id),
                        'confirmDelete' => true,
                    ])->render();
                }

                return $aksi;
            })
            ->rawColumns(['drag-handle', 'ceklist', 'aksi'])
            ->make();
    }

    public function form($id = null)
    {
        isCan('u');

        $menu = $id ? AnjunganMenu::findOrFail($id) : new AnjunganMenu();

        return view('anjungan::backend.menu.form', [
            'action'                    => $id ? 'Ubah' : 'Tambah',
            'menu'                      => $menu,
            'form_action'               => $id ? ci_route('anjungan_menu.update', $id) : ci_route('anjungan_menu.insert'),
            'link_tipe'                 => $this->linkTypeOptions(),
            'artikel_statis'            => Artikel::statis()->select(['id', 'judul'])->orderBy('judul')->get()->toArray(),
            'kategori_artikel'          => Kategori::select(['slug', 'kategori'])->orderBy('kategori')->get()->toArray(),
            'statistik_penduduk'        => [],
            'statistik_keluarga'        => [],
            'statistik_kategori_bantuan'=> [],
            'statistik_program_bantuan' => [],
            'statis_lainnya'            => [],
            'artikel_keuangan'          => [],
            'kelompok'                  => Kelompok::select(['id', 'nama', 'master'])->orderBy('nama')->get()->toArray(),
            'lembaga'                   => $this->daftarLembaga(),
            'suplemen'                  => Suplemen::select(['id', 'nama'])->orderBy('nama')->get()->toArray(),
        ]);
    }

    public function insert(): void
    {
        isCan('u');

        $data = $this->payload();

        if (! AnjunganMenu::create($data)) {
            redirect_with('error', 'Gagal Tambah Data');
        }

        redirect_with('success', 'Berhasil Tambah Data');
    }

    public function update($id): void
    {
        isCan('u');

        $menu = AnjunganMenu::findOrFail($id);
        if (! $menu->update($this->payload($menu))) {
            redirect_with('error', 'Gagal Ubah Data');
        }

        redirect_with('success', 'Berhasil Ubah Data');
    }

    public function delete($id = null): void
    {
        isCan('h');

        $ids = $id ? [$id] : (array) $this->input->post('id_cb');
        $ok  = AnjunganMenu::whereIn('id', array_filter($ids))->delete();

        if (! $ok) {
            redirect_with('error', 'Gagal Hapus Data');
        }

        redirect_with('success', 'Berhasil Hapus Data');
    }

    public function delete_all(): void
    {
        $this->delete();
    }

    public function lock($id = null): void
    {
        isCan('u');

        $menu         = AnjunganMenu::findOrFail($id);
        $menu->status = $menu->status ? 0 : 1;
        $menu->save();

        redirect_with('success', 'Berhasil Ubah Status');
    }

    public function tukar()
    {
        isCan('u');

        $ids = array_filter((array) $this->input->post('data'));
        foreach ($ids as $index => $id) {
            AnjunganMenu::where('id', $id)->update(['urut' => $index + 1]);
        }

        return response()->json(['status' => true]);
    }

    private function payload(?AnjunganMenu $menu = null): array
    {
        $icon = $menu?->icon;

        if (! empty($_FILES['icon']['name'])) {
            $icon = $this->uploadIcon('icon');
        }

        return [
            'nama'      => strip_tags(trim((string) $this->input->post('nama'))),
            'icon'      => $icon,
            'link_tipe' => (int) $this->input->post('link_tipe'),
            'link'      => strip_tags(trim((string) $this->input->post('link'))),
            'status'    => 1,
        ];
    }

    private function linkTypeOptions(): array
    {
        return [
            1 => 'Artikel Statis',
            2 => 'Statistik Penduduk',
            3 => 'Statistik Keluarga',
            4 => 'Statistik Program Bantuan',
            5 => 'Halaman Statis Lainnya',
            6 => 'Artikel Keuangan',
            7 => 'Kelompok/Lembaga',
            8 => 'Kategori Artikel',
            9 => 'Data Suplemen',
            10 => 'Status IDM',
            11 => 'Data Lembaga',
            99 => 'Link Eksternal',
        ];
    }

    private function daftarLembaga(): array
    {
        if (! DB::getSchemaBuilder()->hasTable('lembaga')) {
            return [];
        }

        return DB::table('lembaga')->select(['id', 'nama', 'master'])->orderBy('nama')->get()->toArray();
    }
    
    private function uploadIcon(string $field): string
    {
        $file = $_FILES[$field] ?? null;
        if (! $file || $file['error'] !== UPLOAD_ERR_OK) {
            return '';
        }

        $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed   = ['gif', 'jpg', 'jpeg', 'png', 'webp'];
        $filename  = sha1_file($file['tmp_name']) . '-' . time() . '.' . $ext;
        $targetDir = FCPATH . LOKASI_ICON_MENU_ANJUNGAN;

        if (! in_array($ext, $allowed, true)) {
            redirect_with('error', 'Format icon tidak didukung.');
        }

        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0775, true);
        }

        if (! move_uploaded_file($file['tmp_name'], $targetDir . $filename)) {
            redirect_with('error', 'Gagal mengunggah icon menu.');
        }

        return $filename;
    }
}
