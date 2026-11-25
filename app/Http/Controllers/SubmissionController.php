<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Submission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SubmissionController extends Controller
{
    public function store(Request $request, Assignment $assignment): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Auth::user()->isMahasiswa()) {
            abort(403, 'Akses ditolak. Pengumpulan tugas hanya dapat dilakukan oleh Mahasiswa.');
        }

        $user = Auth::user();

        $enrollment = $assignment->course->students()->where('users.id', $user->id)->first();
        if (!$enrollment || $enrollment->pivot->status !== 'approved') {
            abort(403, 'Akses ditolak. Anda belum disetujui oleh Dosen untuk mengikuti mata kuliah tugas ini.');
        }

        if ($assignment->isOverdue()) {
            return back()->with('error', 'Gagal mengumpulkan: Batas waktu pengumpulan (deadline) tugas telah berakhir.');
        }

        $validated = $request->validate([
            'jawaban' => ['required', 'string', 'min:10', 'max:20000'],
        ], [
            'jawaban.required' => 'Teks jawaban tugas wajib diisi.',
            'jawaban.min' => 'Jawaban terlalu pendek. Tuliskan jawaban minimal 10 karakter.',
            'jawaban.max' => 'Jawaban melebihi batas maksimal 20.000 karakter.',
        ]);

        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment->id,
                'user_id' => $user->id,
            ],
            [
                'jawaban' => $validated['jawaban'],
                'submitted_at' => now(),
            ]
        );

        return back()->with('success', 'Jawaban tugas Anda berhasil disimpan ke sistem.');
    }

    public function grade(Request $request, Submission $submission): RedirectResponse
    {
        // Pastikan dosen merupakan pengampu mata kuliah.
        if ($submission->assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // Validasi nilai dan catatan dosen.
        $validated = $request->validate([
            'nilai' => ['required', 'integer', 'min:0', 'max:100'],
            'catatan_dosen' => ['nullable', 'string', 'max:1000'],
        ], [
            'nilai.required' => 'Nilai wajib diisi.',
            'nilai.integer' => 'Nilai harus berupa angka.',
            'nilai.min' => 'Nilai minimal adalah 0.',
            'nilai.max' => 'Nilai maksimal adalah 100.',
            'catatan_dosen.max' => 'Catatan dosen maksimal 1000 karakter.',
        ]);

        // Simpan nilai dan catatan dosen.
        $submission->update([
            'nilai' => $validated['nilai'],
            'catatan_dosen' => $validated['catatan_dosen'] ?? null,
        ]);

        return back()->with('success', 'Nilai tugas mahasiswa berhasil disimpan.');
    }

    public function exportCsv(Assignment $assignment): StreamedResponse
    {
        // Pastikan dosen merupakan pengampu mata kuliah.
        if ($assignment->course->dosen_id !== Auth::id()) {
            abort(403, 'Akses ditolak. Anda bukan dosen pengampu mata kuliah ini.');
        }

        // Muat data mahasiswa yang sudah disetujui dan pengumpulan tugas.
        $assignment->load([
            'course.approvedStudents',
            'submissions.user',
        ]);

        // Tentukan nama file CSV.
        $fileName = 'laporan_nilai_' . $assignment->id . '.csv';

        return response()->streamDownload(function () use ($assignment) {
            $handle = fopen('php://output', 'w');

            // Tambahkan BOM agar karakter UTF-8 terbaca dengan baik di Excel.
            fwrite($handle, "\xEF\xBB\xBF");

            // Header CSV.
            fputcsv($handle, [
                'NIM',
                'Nama Mahasiswa',
                'Email',
                'Jawaban',
                'Tanggal Pengumpulan',
                'Nilai',
                'Catatan Dosen',
                'Status Nilai',
            ]);

            // Ambil data pengumpulan berdasarkan mahasiswa.
            foreach ($assignment->course->approvedStudents as $student) {
                $submission = $assignment->submissions
                    ->firstWhere('user_id', $student->id);

                fputcsv($handle, [
                    $student->nomor_induk ?? '-',
                    $student->name,
                    $student->email,
                    $submission->jawaban ?? '-',
                    $submission && $submission->submitted_at
                        ? $submission->submitted_at->format('Y-m-d H:i:s')
                        : '-',
                    $submission->nilai ?? 'Belum Dinilai',
                    $submission->catatan_dosen ?? '-',
                    $submission->status_nilai ?? 'Belum Dinilai',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
