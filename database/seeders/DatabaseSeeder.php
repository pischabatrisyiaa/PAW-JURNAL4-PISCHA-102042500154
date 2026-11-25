<?php

namespace Database\Seeders;

use App\Models\Assignment;
use App\Models\MataKuliah;
use App\Models\Material;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Akun Dosen Pengampu Default
        $dosen = User::updateOrCreate(
            ['email' => 'dosen@celoe.test'],
            [
                'name' => 'Dr. Ir. Budi Santoso, M.T.',
                'password' => Hash::make('dosen123'),
                'role' => 'dosen',
                'nomor_induk' => '198501012015041001',
            ]
        );

        // 2. Akun Mahasiswa Default
        $mahasiswa1 = User::updateOrCreate(
            ['email' => 'mahasiswa@celoe.test'],
            [
                'name' => 'Ahmad Pratama',
                'password' => Hash::make('mahasiswa123'),
                'role' => 'mahasiswa',
                'nomor_induk' => '1301210001',
            ]
        );

        // 3. Mata Kuliah Semester Pertama
        $course1 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF301'],
            [
                'nama_mk' => 'Pengembangan Aplikasi Website',
                'deskripsi' => 'Mempelajari Laravel, MVC, Eloquent ORM, dan keamanan web.',
                'sks' => 4,
                'semester' => 'Ganjil 2026/2027',
                'dosen_id' => $dosen->id,
            ]
        );

        // 4. Pendaftaran Mahasiswa Pertama
        $course1->students()->syncWithoutDetaching([
            $mahasiswa1->id => ['status' => 'approved'],
        ]);

        // 5. Materi Contoh
        Material::updateOrCreate(
            [
                'course_id' => $course1->id,
                'minggu_ke' => 1,
                'urutan' => 1,
            ],
            [
                'judul' => 'Pengenalan Arsitektur MVC & Routing di Laravel 12',
                'konten' => 'Mempelajari HTTP Request, Route Middleware, Controller, dan Blade Template.',
            ]
        );

        // TODO 8: Tambahan data awal

        // 6. Akun Mahasiswa Tambahan
        $mahasiswa2 = User::updateOrCreate(
            ['email' => 'mahasiswa2@celoe.test'],
            [
                'name' => 'Siti Rahmawati',
                'password' => Hash::make('mahasiswa123'),
                'role' => 'mahasiswa',
                'nomor_induk' => '1301210002',
            ]
        );

        // 7. Mata Kuliah Kedua
        $course2 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF302'],
            [
                'nama_mk' => 'Basis Data',
                'deskripsi' => 'Mempelajari perancangan dan pengelolaan basis data.',
                'sks' => 3,
                'semester' => 'Genap 2026/2027',
                'dosen_id' => $dosen->id,
            ]
        );

        // 8. Mata Kuliah Ketiga 
        $course3 = MataKuliah::updateOrCreate(
            ['kode_mk' => 'IF303'],
            [
                'nama_mk' => 'Pemrograman Berorientasi Objek',
                'deskripsi' => 'Mempelajari konsep pemrograman berorientasi objek.',
                'sks' => 3,
                'semester' => 'Ganjil 2027/2028',
                'dosen_id' => $dosen->id,
            ]
        );

        // 9. Pendaftaran Mahasiswa di Mata Kuliah Kedua dan Ketiga
        $course2->students()->syncWithoutDetaching([
            $mahasiswa1->id => ['status' => 'approved'],
        ]);

        $course3->students()->syncWithoutDetaching([
            $mahasiswa2->id => ['status' => 'approved'],
        ]);

        // 10. Tugas Pertama
        $assignment1 = Assignment::updateOrCreate(
            [
                'course_id' => $course1->id,
                'judul' => 'Tugas Praktikum Laravel',
            ],
            [
                'deskripsi' => 'Buat aplikasi sederhana menggunakan Laravel.',
                'deadline' => now()->addDays(7),
            ]
        );

        // 11. Tugas Kedua
        $assignment2 = Assignment::updateOrCreate(
            [
                'course_id' => $course2->id,
                'judul' => 'Tugas Perancangan Basis Data',
            ],
            [
                'deskripsi' => 'Buat rancangan database menggunakan ERD.',
                'deadline' => now()->addDays(14),
            ]
        );

        // 12. Contoh Submission Pertama
        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment1->id,
                'user_id' => $mahasiswa1->id,
            ],
            [
                'jawaban' => 'Saya telah menyelesaikan tugas praktikum Laravel.',
                'submitted_at' => now()->subDays(1),
            ]
        );

        // 13. Contoh Submission Kedua
        Submission::updateOrCreate(
            [
                'assignment_id' => $assignment1->id,
                'user_id' => $mahasiswa2->id,
            ],
            [
                'jawaban' => 'Saya telah menyelesaikan aplikasi Laravel.',
                'submitted_at' => now()->subHours(5),
            ]
        );
    }
}
