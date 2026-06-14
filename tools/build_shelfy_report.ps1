param(
    [string]$SourcePath = "C:\Users\THA\Downloads\Laporan TuBes - Nama Aplikasi.docx",
    [string]$OutputPath = "D:\Program Files\xampp\htdocs\laravel\shelfy\docs\Laporan TuBes SHELFY - Basis Data II.docx",
    [string]$PdfPath = "D:\Program Files\xampp\htdocs\laravel\shelfy\docs\Laporan TuBes SHELFY - Basis Data II.pdf",
    [string]$ModalImagePath = "D:\Program Files\xampp\htdocs\laravel\shelfy\docs\report-assets\modal-perpanjangan-desktop.png"
)

$ErrorActionPreference = "Stop"

if (-not (Test-Path -LiteralPath $SourcePath)) {
    throw "Template laporan tidak ditemukan: $SourcePath"
}

$outputDirectory = Split-Path -Parent $OutputPath
New-Item -ItemType Directory -Force -Path $outputDirectory | Out-Null
Copy-Item -LiteralPath $SourcePath -Destination $OutputPath -Force

$wdCollapseEnd = 0
$wdPageBreak = 7
$wdAlignLeft = 0
$wdAlignCenter = 1
$wdAlignRight = 2
$wdAlignJustify = 3
$wdLineSpaceSingle = 0
$wdStyleNormal = -1
$wdStyleHeading1 = -2
$wdStyleHeading2 = -3
$wdStyleHeading3 = -4
$wdStyleTitle = -63
$wdStyleSubtitle = -75
$wdFieldPage = 33
$wdExportFormatPDF = 17
$wdAutoFitWindow = 2
$wdPreferredWidthPoints = 3
$wdBorderBottom = -3
$wdBorderTop = -1
$wdBorderLeft = -2
$wdBorderRight = -4
$wdBorderHorizontal = -5
$wdBorderVertical = -6

$word = $null
$document = $null

function Reset-SelectionFormat {
    param($Selection)

    $Selection.Style = $wdStyleNormal
    $Selection.Font.Name = "Aptos"
    $Selection.Font.Size = 11
    $Selection.Font.Bold = 0
    $Selection.Font.Italic = 0
    $Selection.Font.Color = 0
    $Selection.ParagraphFormat.Alignment = $wdAlignJustify
    $Selection.ParagraphFormat.SpaceBefore = 0
    $Selection.ParagraphFormat.SpaceAfter = 6
    $Selection.ParagraphFormat.LineSpacingRule = $wdLineSpaceSingle
    $Selection.ParagraphFormat.KeepWithNext = 0
}

function Add-Paragraph {
    param(
        $Selection,
        [string]$Text,
        [int]$Style = $wdStyleNormal,
        [int]$Alignment = $wdAlignJustify,
        [double]$SpaceAfter = 6,
        [switch]$Bold,
        [switch]$Italic,
        [double]$FontSize = 11
    )

    Reset-SelectionFormat $Selection
    $Selection.Style = $Style
    $Selection.Font.Size = $FontSize
    $Selection.Font.Bold = [int]$Bold.IsPresent
    $Selection.Font.Italic = [int]$Italic.IsPresent
    $Selection.ParagraphFormat.Alignment = $Alignment
    $Selection.ParagraphFormat.SpaceAfter = $SpaceAfter
    if ($Style -in @($wdStyleHeading1, $wdStyleHeading2, $wdStyleHeading3)) {
        $Selection.ParagraphFormat.KeepWithNext = -1
    }
    $Selection.TypeText($Text)
    $Selection.TypeParagraph()
}

function Add-Bullets {
    param($Selection, [string[]]$Items)

    foreach ($item in $Items) {
        Reset-SelectionFormat $Selection
        $Selection.ParagraphFormat.LeftIndent = 18
        $Selection.ParagraphFormat.FirstLineIndent = -12
        $Selection.TypeText("- $item")
        $Selection.TypeParagraph()
    }
    $Selection.ParagraphFormat.LeftIndent = 0
    $Selection.ParagraphFormat.FirstLineIndent = 0
}

function Add-NumberedSteps {
    param($Selection, [string[]]$Items)

    for ($index = 0; $index -lt $Items.Count; $index++) {
        Reset-SelectionFormat $Selection
        $Selection.ParagraphFormat.LeftIndent = 18
        $Selection.ParagraphFormat.FirstLineIndent = -18
        $Selection.TypeText("$($index + 1). $($Items[$index])")
        $Selection.TypeParagraph()
    }
    $Selection.ParagraphFormat.LeftIndent = 0
    $Selection.ParagraphFormat.FirstLineIndent = 0
}

function Add-Table {
    param(
        $Document,
        $Selection,
        [string[]]$Headers,
        [object[]]$Rows,
        [double[]]$Widths = @()
    )

    $rowCount = $Rows.Count + 1
    $columnCount = $Headers.Count
    $range = $Selection.Range
    $table = $Document.Tables.Add($range, $rowCount, $columnCount)
    $table.AllowAutoFit = $false
    $table.Range.Font.Name = "Aptos"
    $table.Range.Font.Size = 9.5
    $table.Range.ParagraphFormat.SpaceAfter = 2
    $table.Range.ParagraphFormat.Alignment = $wdAlignLeft

    for ($column = 1; $column -le $columnCount; $column++) {
        $cell = $table.Cell(1, $column)
        $cell.Range.Text = $Headers[$column - 1]
        $cell.Range.Font.Bold = 1
        $cell.Range.Font.Color = 16777215
        $cell.Shading.BackgroundPatternColor = 8947848
    }

    for ($row = 0; $row -lt $Rows.Count; $row++) {
        for ($column = 0; $column -lt $columnCount; $column++) {
            $table.Cell($row + 2, $column + 1).Range.Text = [string]$Rows[$row][$column]
            if (($row % 2) -eq 1) {
                $table.Cell($row + 2, $column + 1).Shading.BackgroundPatternColor = 15987699
            }
        }
    }

    if ($Widths.Count -eq $columnCount) {
        for ($column = 1; $column -le $columnCount; $column++) {
            $table.Columns.Item($column).PreferredWidthType = $wdPreferredWidthPoints
            $table.Columns.Item($column).PreferredWidth = $Widths[$column - 1]
        }
    } else {
        $table.AutoFitBehavior($wdAutoFitWindow)
    }

    foreach ($borderId in @($wdBorderBottom, $wdBorderTop, $wdBorderLeft, $wdBorderRight, $wdBorderHorizontal, $wdBorderVertical)) {
        $table.Borders.Item($borderId).Color = 13421772
    }

    $Selection.SetRange($table.Range.End, $table.Range.End)
    $Selection.TypeParagraph()
    return $table
}

function Add-CodeBlock {
    param($Document, $Selection, [string]$Code)

    $range = $Selection.Range
    $table = $Document.Tables.Add($range, 1, 1)
    $table.AllowAutoFit = $false
    $table.Columns.Item(1).PreferredWidthType = $wdPreferredWidthPoints
    $table.Columns.Item(1).PreferredWidth = 450
    $cell = $table.Cell(1, 1)
    $cell.Range.Text = $Code.Trim()
    $cell.Range.Font.Name = "Consolas"
    $cell.Range.Font.Size = 8.5
    $cell.Range.ParagraphFormat.Alignment = $wdAlignLeft
    $cell.Range.ParagraphFormat.SpaceAfter = 0
    $cell.Shading.BackgroundPatternColor = 15790320
    $Selection.SetRange($table.Range.End, $table.Range.End)
    $Selection.TypeParagraph()
}

function Add-Image {
    param($Document, $Selection, [string]$Path, [double]$Width = 450)

    if (-not (Test-Path -LiteralPath $Path)) {
        return
    }

    $Selection.ParagraphFormat.Alignment = $wdAlignCenter
    $image = $Selection.InlineShapes.AddPicture($Path, $false, $true)
    $image.LockAspectRatio = -1
    $image.Width = $Width
    $Selection.TypeParagraph()
    $Selection.ParagraphFormat.Alignment = $wdAlignJustify
}

try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    $document = $word.Documents.Open($OutputPath)

    # Use the supplied file as the document base, then rebuild its generic content
    # so the result contains only the SHELFY report and no template placeholders.
    $document.Content.Delete()
    while ($document.InlineShapes.Count -gt 0) {
        $document.InlineShapes.Item(1).Delete()
    }
    while ($document.Shapes.Count -gt 0) {
        $document.Shapes.Item(1).Delete()
    }

    foreach ($section in $document.Sections) {
        $section.PageSetup.TopMargin = 54
        $section.PageSetup.BottomMargin = 54
        $section.PageSetup.LeftMargin = 62
        $section.PageSetup.RightMargin = 62
        $section.PageSetup.DifferentFirstPageHeaderFooter = -1
        $header = $section.Headers.Item(1).Range
        $header.Text = "SHELFY | Laporan Tugas Besar Basis Data II"
        $header.Font.Name = "Aptos"
        $header.Font.Size = 8
        $header.Font.Color = 8421504
        $header.ParagraphFormat.Alignment = $wdAlignRight
        $footer = $section.Footers.Item(1).Range
        $footer.ParagraphFormat.Alignment = $wdAlignCenter
        $footer.Font.Name = "Aptos"
        $footer.Font.Size = 9
        $footer.Fields.Add($footer, $wdFieldPage) | Out-Null
        $section.Headers.Item(2).Range.Text = ""
        $section.Footers.Item(2).Range.Text = ""
    }

    $normalStyle = $document.Styles.Item($wdStyleNormal)
    $normalStyle.Font.Name = "Aptos"
    $normalStyle.Font.Size = 11
    $normalStyle.ParagraphFormat.Alignment = $wdAlignJustify
    $normalStyle.ParagraphFormat.SpaceAfter = 6

    foreach ($styleId in @($wdStyleHeading1, $wdStyleHeading2, $wdStyleHeading3)) {
        $style = $document.Styles.Item($styleId)
        $style.Font.Name = "Aptos Display"
        $style.Font.Color = 6579300
        $style.Font.Bold = -1
    }

    $selection = $word.Selection
    $selection.HomeKey(6) | Out-Null

    Add-Paragraph $selection "LAPORAN TUGAS BESAR" $wdStyleTitle $wdAlignCenter 2 -Bold -FontSize 18
    Add-Paragraph $selection "SISTEM BASIS DATA II" $wdStyleTitle $wdAlignCenter 32 -Bold -FontSize 18
    Add-Paragraph $selection "SHELFY" $wdStyleTitle $wdAlignCenter 4 -Bold -FontSize 30
    Add-Paragraph $selection "Aplikasi Perpustakaan Digital Berbasis MongoDB" $wdStyleSubtitle $wdAlignCenter 4 -Bold -FontSize 16
    Add-Paragraph $selection "Implementasi Laravel MVC dan Laravel Breeze" $wdStyleSubtitle $wdAlignCenter 38 -Italic -FontSize 12
    Add-Paragraph $selection "Disusun oleh:" $wdStyleNormal $wdAlignCenter 10 -Bold -FontSize 11
    Add-Paragraph $selection "Michael Eluzai Situmorang (70701240001)" $wdStyleNormal $wdAlignCenter 3 -FontSize 10.5
    Add-Paragraph $selection "Muhammad Anantha Mahardika Ridwan (707012400122)" $wdStyleNormal $wdAlignCenter 3 -FontSize 10.5
    Add-Paragraph $selection "Muhammad Fadhil Athallah (70701240003)" $wdStyleNormal $wdAlignCenter 3 -FontSize 10.5
    Add-Paragraph $selection "Mumpuni Nur Idzati Ayuningtyas (70701240002)" $wdStyleNormal $wdAlignCenter 35 -FontSize 10.5
    Add-Paragraph $selection "Program Studi D4 Sistem Informasi Kota Cerdas" $wdStyleNormal $wdAlignCenter 2 -Bold -FontSize 11
    Add-Paragraph $selection "Fakultas Ilmu Terapan" $wdStyleNormal $wdAlignCenter 2 -Bold -FontSize 11
    Add-Paragraph $selection "TELKOM UNIVERSITY" $wdStyleNormal $wdAlignCenter 6 -Bold -FontSize 11
    Add-Paragraph $selection "2026" $wdStyleNormal $wdAlignCenter 0 -Bold -FontSize 11
    $selection.InsertBreak($wdPageBreak)

    Add-Paragraph $selection "DAFTAR ISI" $wdStyleTitle $wdAlignCenter 18 -Bold -FontSize 18
    $tocRange = $selection.Range
    $toc = $document.TablesOfContents.Add($tocRange, $true, 1, 3)
    $selection.SetRange($toc.Range.End, $toc.Range.End)
    $selection.TypeParagraph()
    $selection.InsertBreak($wdPageBreak)

    Add-Paragraph $selection "BAB I" $wdStyleHeading1 $wdAlignCenter 0
    Add-Paragraph $selection "PENDAHULUAN" $wdStyleHeading1 $wdAlignCenter 12
    Add-Paragraph $selection "1.1 Latar Belakang" $wdStyleHeading2
    Add-Paragraph $selection "Perpustakaan membutuhkan data buku, anggota, peminjaman, pengembalian, dan rekapitulasi yang konsisten serta mudah dicari. Pengelolaan manual berisiko menimbulkan kesalahan stok, keterlambatan yang tidak tercatat, dan laporan yang lambat disusun. SHELFY dikembangkan sebagai aplikasi web perpustakaan digital untuk mendukung proses tersebut menggunakan basis data NoSQL MongoDB dan pola Model-View-Controller (MVC) pada Laravel."
    Add-Paragraph $selection "Pemilihan MongoDB sesuai kebutuhan Tugas Besar Basis Data II karena data disimpan sebagai dokumen BSON, bukan tabel relasional MySQL. Struktur dokumen memberi fleksibilitas untuk menyimpan riwayat perpanjangan, data pembayaran, dan snapshot informasi buku/anggota pada transaksi. Aplikasi memiliki tiga peran, yaitu admin, pustakawan, dan mahasiswa, dengan hak akses yang berbeda."

    Add-Paragraph $selection "1.2 Rumusan Masalah" $wdStyleHeading2
    Add-Bullets $selection @(
        "Bagaimana mengelola akun, profil anggota, buku, stok, dan transaksi perpustakaan menggunakan MongoDB?",
        "Bagaimana menerapkan CRUD, pencarian, filter, serta rekapitulasi SUM, AVG, Greater Than, dan pengelompokan kategori?",
        "Bagaimana memisahkan kewenangan admin, pustakawan, dan mahasiswa secara aman?",
        "Bagaimana membuat alur keranjang, checkout, bukti pengambilan, perpanjangan, pengembalian manual, denda, dan nota PDF?"
    )

    Add-Paragraph $selection "1.3 Tujuan" $wdStyleHeading2
    Add-Bullets $selection @(
        "Membangun aplikasi perpustakaan berbasis web yang dapat dijalankan melalui XAMPP dan Laravel.",
        "Menggunakan MongoDB sebagai satu-satunya basis data aplikasi.",
        "Menyediakan fitur CRUD, filter, transaksi, dan rekapitulasi yang dapat didemonstrasikan.",
        "Menerapkan Laravel Breeze untuk autentikasi serta MVC untuk pemisahan tanggung jawab kode.",
        "Menyediakan bukti pengembalian yang dapat dicetak atau disimpan sebagai PDF."
    )

    Add-Paragraph $selection "1.4 Ruang Lingkup" $wdStyleHeading2
    Add-Paragraph $selection "Aplikasi mencakup registrasi mahasiswa, login semua peran, pengelolaan profil, katalog dan detail buku, pencarian/filter, keranjang, checkout, konfirmasi pengambilan, perpanjangan, pengembalian, perhitungan denda, pembayaran formalitas, nota, dashboard, dan rekapitulasi. Pembayaran tidak terhubung ke payment gateway nyata dan nota menggunakan mekanisme cetak browser."

    $selection.InsertBreak($wdPageBreak)
    Add-Paragraph $selection "BAB II" $wdStyleHeading1 $wdAlignCenter 0
    Add-Paragraph $selection "LANDASAN TEORI DAN TEKNOLOGI" $wdStyleHeading1 $wdAlignCenter 12

    Add-Paragraph $selection "2.1 Basis Data NoSQL dan MongoDB" $wdStyleHeading2
    Add-Paragraph $selection "NoSQL adalah pendekatan penyimpanan data nonrelasional. MongoDB menggunakan model document database: satu record disimpan sebagai dokumen BSON dan sekumpulan dokumen disimpan di dalam collection. Setiap dokumen memiliki _id bertipe ObjectId. MongoDB cocok untuk SHELFY karena transaksi dapat memiliki atribut yang berkembang, misalnya riwayat perpanjangan berupa array embedded document."

    Add-Paragraph $selection "2.2 Laravel MVC dan Breeze" $wdStyleHeading2
    Add-Paragraph $selection "Laravel memisahkan aplikasi menjadi Model, View, dan Controller. Model berhubungan dengan collection MongoDB; Controller menerima request, memvalidasi data, menjalankan aturan bisnis, dan memilih response; View Blade menampilkan data. Laravel Breeze menyediakan fondasi login, register, logout, pengelolaan password, dan profil. Pada SHELFY, alur Breeze disesuaikan agar pengguna yang baru register kembali ke halaman login dan diarahkan ke halaman sesuai role setelah berhasil masuk."

    Add-Paragraph $selection "2.3 Teknologi yang Digunakan" $wdStyleHeading2
    Add-Table $document $selection @("Teknologi", "Kegunaan") @(
        @("PHP 8.3 dan Laravel", "Backend, routing, validasi, middleware, controller, dan Blade"),
        @("Laravel Breeze", "Autentikasi dan pengelolaan akun"),
        @("mongodb/laravel-mongodb", "Driver Eloquent MongoDB untuk model Laravel"),
        @("MongoDB Community Server", "Penyimpanan dokumen NoSQL pada database shelfy_db"),
        @("MongoDB Compass", "Melihat collection dan dokumen secara visual"),
        @("Blade, CSS, JavaScript", "Antarmuka web responsif dan dialog tanpa React"),
        @("Vite", "Build aset CSS dan JavaScript"),
        @("Laravel Storage", "Penyimpanan cover buku dan foto profil"),
        @("Browser Print", "Menghasilkan nota dalam bentuk PDF")
    ) @(145, 323) | Out-Null

    $selection.InsertBreak($wdPageBreak)
    Add-Paragraph $selection "BAB III" $wdStyleHeading1 $wdAlignCenter 0
    Add-Paragraph $selection "ANALISIS DAN PERANCANGAN" $wdStyleHeading1 $wdAlignCenter 12

    Add-Paragraph $selection "3.1 Aktor dan Hak Akses" $wdStyleHeading2
    Add-Table $document $selection @("Role", "Hak Akses Utama") @(
        @("Admin", "Dashboard dan rekap, melihat buku/anggota/transaksi, mengelola akun sendiri, dan akses pengawasan."),
        @("Pustakawan", "CRUD buku dan stok, unggah cover, bukti pengambilan, perpanjangan, pengembalian, denda, pembayaran, dan nota."),
        @("Mahasiswa", "Register/login, katalog, pencarian, detail buku, keranjang, checkout, riwayat pinjaman, pembayaran formalitas, nota, dan profil sendiri.")
    ) @(105, 363) | Out-Null

    Add-Paragraph $selection "3.2 Arsitektur MVC" $wdStyleHeading2
    Add-NumberedSteps $selection @(
        "Browser mengirim request ke route pada routes/web.php.",
        "Middleware auth dan role memeriksa identitas serta kewenangan pengguna.",
        "Controller memvalidasi input dan menjalankan aturan bisnis.",
        "Model membaca atau mengubah dokumen MongoDB melalui mongodb/laravel-mongodb.",
        "Controller mengirim data ke View Blade atau melakukan redirect dengan pesan status.",
        "Blade merender HTML, sedangkan Vite menyediakan CSS dan JavaScript."
    )

    Add-Paragraph $selection "3.3 Mengapa Ada Collection users dan members?" $wdStyleHeading2
    Add-Paragraph $selection "Kedua collection tidak duplikat karena tanggung jawabnya berbeda. Collection users adalah data autentikasi dan otorisasi. Isinya meliputi email login, password yang telah di-hash, role, status akun, avatar, dan referensi member_id. Collection members adalah data keanggotaan perpustakaan: nama, NIM, program studi, nomor telepon, alamat, status anggota, dan referensi user_id."
    Add-Paragraph $selection "Pemisahan ini membuat data sensitif login tidak bercampur dengan profil operasional perpustakaan. Admin dan pustakawan dapat memiliki dokumen users tanpa harus menjadi anggota peminjam. Mahasiswa memiliki hubungan satu-ke-satu: users.member_id menunjuk members._id, sedangkan members.user_id menunjuk users._id. Transaksi loans menggunakan member_id agar tetap terkait pada identitas perpustakaan."
    Add-Table $document $selection @("Aspek", "users", "members") @(
        @("Tujuan", "Login dan hak akses", "Profil anggota perpustakaan"),
        @("Data utama", "email, password, role, status", "NIM, prodi, telepon, alamat"),
        @("Pemilik", "Admin, pustakawan, mahasiswa", "Mahasiswa/anggota peminjam"),
        @("Keamanan", "Password disimpan sebagai hash", "Tidak menyimpan password"),
        @("Relasi", "member_id -> members._id", "user_id -> users._id")
    ) @(85, 190, 193) | Out-Null

    Add-Paragraph $selection "3.4 Rancangan Collection MongoDB" $wdStyleHeading2
    Add-Table $document $selection @("Collection", "Isi dan Peran") @(
        @("users", "Akun autentikasi, role, status, avatar, dan referensi anggota."),
        @("members", "Profil mahasiswa/anggota untuk kebutuhan transaksi perpustakaan."),
        @("books", "Metadata buku, kategori, ISBN, stok total/tersedia, cover, dan statistik."),
        @("cart_items", "Pilihan buku sementara milik mahasiswa sebelum checkout."),
        @("loans", "Transaksi peminjaman sampai pengembalian, denda, pembayaran, nota, serta riwayat perpanjangan."),
        @("sessions", "Sesi login Laravel ketika session driver menggunakan database."),
        @("cache dan cache_locks", "Cache internal Laravel; bukan data utama aplikasi."),
        @("jobs, job_batches, failed_jobs", "Infrastruktur antrean Laravel; dapat tetap kosong bila queue tidak digunakan."),
        @("migrations dan password_reset_tokens", "Riwayat struktur aplikasi dan token reset password.")
    ) @(120, 348) | Out-Null

    Add-Paragraph $selection "3.5 Kompleksitas Basis Data" $wdStyleHeading2
    Add-Bullets $selection @(
        "Reference satu-ke-satu antara users dan members untuk memisahkan keamanan akun dari profil domain.",
        "Reference antarcollection: loans menyimpan book_id dan member_id; cart_items menyimpan user_id dan book_id.",
        "Denormalisasi snapshot judul_buku, nama_anggota, NIM, dan kategori pada loans agar riwayat transaksi tetap terbaca jika data master berubah.",
        "Embedded array riwayat_perpanjangan menyimpan tanggal lama, tanggal baru, alasan, waktu proses, dan nama petugas dalam satu dokumen loan.",
        "Transisi status menunggu_diambil, dipinjam, terlambat, dan dikembalikan disertai perubahan stok.",
        "Rekapitulasi menggunakan count, sum, avg, filter greater-than, groupBy, dan sortByDesc atas dokumen MongoDB.",
        "Validasi kepemilikan memastikan mahasiswa hanya melihat transaksi dan keranjang miliknya sendiri."
    )

    Add-Paragraph $selection "3.6 Struktur Folder Backend" $wdStyleHeading2
    Add-CodeBlock $document $selection @'
app/
  Http/Controllers/       # Controller auth dan modul SHELFY
  Http/Middleware/        # Pemeriksaan role admin/staff/student
  Models/                 # User, Member, Book, Loan, CartItem
  Support/                # Helper Shelfy dan ShelfySeeder
resources/views/          # Blade layout, partial, admin, dan mahasiswa
routes/web.php            # Pemetaan URL ke controller
database/seeders/         # Pemanggil data awal
storage/app/public/       # Cover buku dan foto profil
tests/Feature/            # Pengujian alur aplikasi
'@

    $selection.InsertBreak($wdPageBreak)
    Add-Paragraph $selection "BAB IV" $wdStyleHeading1 $wdAlignCenter 0
    Add-Paragraph $selection "IMPLEMENTASI BACKEND DAN FITUR" $wdStyleHeading1 $wdAlignCenter 12

    Add-Paragraph $selection "4.1 Koneksi MongoDB" $wdStyleHeading2
    Add-Paragraph $selection "Laravel menggunakan koneksi mongodb sebagai koneksi default. MongoDB Community Server berjalan pada 127.0.0.1 port 27017 dan database aplikasi bernama shelfy_db. Username dan password boleh kosong untuk instalasi lokal tanpa autentikasi."
    Add-CodeBlock $document $selection @'
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=shelfy_db
DB_USERNAME=
DB_PASSWORD=
'@
    Add-Paragraph $selection "Package mongodb/laravel-mongodb mendaftarkan driver mongodb pada config/database.php. Model kemudian dapat memakai API Eloquent seperti query(), where(), create(), update(), delete(), count(), dan orderBy()."

    Add-Paragraph $selection "4.2 Penjelasan Model" $wdStyleHeading2
    Add-Table $document $selection @("Model", "Tanggung Jawab Backend") @(
        @("User", "Authenticatable MongoDB. Menyimpan akun, hash password, role, status, avatar, member_id, dan helper isAdmin/isLibrarian/isStaff/isStudent."),
        @("Member", "Profil keanggotaan. Menyimpan NIM, prodi, kontak, alamat, status, serta user_id."),
        @("Book", "Data bibliografi dan stok. Integer cast menjaga stok sebagai angka; status tersedia dihitung dari stok_tersedia."),
        @("CartItem", "Keranjang sementara per user sebelum checkout. Mencegah mahasiswa langsung membuat transaksi satu per satu."),
        @("Loan", "Pusat transaksi. Menyimpan tanggal, status, bukti pengambilan, denda, pembayaran, petugas, nota, dan embedded riwayat perpanjangan.")
    ) @(85, 383) | Out-Null

    Add-Paragraph $selection "4.3 Penjelasan Controller" $wdStyleHeading2
    Add-Table $document $selection @("Controller", "Tugas Utama") @(
        @("RegisteredUserController", "Validasi register, membuat Member dan User yang saling terhubung, lalu mengarahkan pengguna ke login tanpa auto-login."),
        @("AuthenticatedSessionController", "Autentikasi, regenerasi session, dan redirect role-aware ke dashboard staff atau dashboard mahasiswa."),
        @("DashboardController", "Menghitung statistik buku, stok, pinjaman, keterlambatan, kategori, rekap, dan buku populer."),
        @("BookController", "CRUD buku, filter, validasi stok/ISBN, upload/hapus cover, serta perlindungan hapus saat masih dipinjam."),
        @("MemberController", "Menampilkan dan memfilter anggota serta informasi pinjaman aktif; tidak mengubah password pengguna."),
        @("StudentController", "Katalog, detail buku, pencarian/filter, keranjang, checkout, pinjaman mahasiswa, dan detail riwayat."),
        @("LoanController", "Daftar transaksi, pencarian/filter status, dan konfirmasi bukti buku telah diambil."),
        @("ReturnController", "Input tanggal kembali manual, hitung keterlambatan/denda, pulihkan stok, dan proses perpanjangan manual."),
        @("ReceiptController", "Otorisasi nota, simulasi pembayaran, konfirmasi pustakawan, dan halaman cetak PDF."),
        @("RecapController", "Rekap count, SUM, AVG, greater-than, keterlambatan, kategori, dan popularitas."),
        @("ProfileController", "Ubah profil akun sendiri, sinkronkan profil anggota, upload foto petugas, dan hapus akun sesuai aturan Breeze.")
    ) @(135, 333) | Out-Null

    Add-Paragraph $selection "4.4 Middleware dan Keamanan" $wdStyleHeading2
    Add-Bullets $selection @(
        "Middleware auth memastikan halaman aplikasi hanya dibuka setelah login.",
        "EnsureAdmin/akses staff membatasi halaman operasional untuk admin dan pustakawan.",
        "EnsureStudent membatasi katalog, keranjang, dan pinjaman saya untuk mahasiswa.",
        "Request validation menolak tanggal, stok, email, file, dan status yang tidak valid.",
        "Password di-hash otomatis melalui cast hashed pada model User dan tidak pernah ditampilkan kembali.",
        "CSRF token melindungi form POST, PUT, PATCH, dan DELETE.",
        "Pemeriksaan ownership pada cart, loan, dan receipt mencegah mahasiswa membuka data mahasiswa lain."
    )

    Add-Paragraph $selection "4.5 Alur Register dan Login" $wdStyleHeading2
    Add-NumberedSteps $selection @(
        "Mahasiswa mengisi nama, NIM, prodi, kontak, email, dan password pada register.",
        "Controller memvalidasi keunikan email dan NIM.",
        "Dokumen Member dibuat terlebih dahulu, kemudian dokumen User dibuat dengan member_id.",
        "Member diperbarui dengan user_id sehingga hubungan dapat ditelusuri dari dua arah.",
        "Setelah register, pengguna tidak langsung masuk; aplikasi menampilkan login.",
        "Login memverifikasi email/password, membuat session baru, lalu mengarahkan halaman berdasarkan role."
    )
    Add-CodeBlock $document $selection @'
// Setelah autentikasi berhasil
$request->session()->regenerate();
return redirect()->route(Shelfy::homeRouteName($request->user()));
'@

    Add-Paragraph $selection "4.6 Alur Buku, Pencarian, Keranjang, dan Checkout" $wdStyleHeading2
    Add-NumberedSteps $selection @(
        "Pustakawan menambah atau mengubah buku melalui dialog, termasuk cover dan stok.",
        "Cover disimpan pada disk public/covers dan URL publik tersedia melalui public/storage.",
        "Mahasiswa membuka katalog lima kartu per baris pada desktop, lalu mencari judul/penulis/ISBN atau memfilter kategori.",
        "Buku tersedia dimasukkan ke cart_items; duplikasi buku pada keranjang yang sama ditolak.",
        "Checkout memvalidasi ketersediaan semua buku dan membuat satu Loan untuk setiap buku.",
        "Status awal menunggu_diambil; stok berkurang setelah transaksi berhasil dibuat."
    )

    Add-Paragraph $selection "4.7 Bukti Pengambilan, Perpanjangan, dan Pengembalian" $wdStyleHeading2
    Add-Paragraph $selection "Pustakawan mengonfirmasi buku telah diambil agar status berubah menjadi dipinjam. Perpanjangan hanya tersedia untuk peminjaman aktif yang belum terlambat. Pustakawan memasukkan tanggal jatuh tempo baru dan alasan. Controller memastikan tanggal baru lebih besar daripada tanggal lama dan hari ini, lalu menambahkan embedded document ke riwayat_perpanjangan."
    Add-CodeBlock $document $selection @'
$history[] = [
    'tanggal_lama' => $oldDueDate->format('Y-m-d'),
    'tanggal_baru' => $newDueDate->format('Y-m-d'),
    'alasan' => $validated['catatan_perpanjangan'],
    'diproses_pada' => now()->format('Y-m-d H:i:s'),
    'diproses_oleh' => $request->user()->displayName(),
];
'@
    Add-Paragraph $selection "Tanggal kembali tidak diisi otomatis karena harus mengikuti kejadian nyata. Pustakawan wajib memilih tanggal manual yang tidak lebih awal dari tanggal pinjam dan tidak melewati tanggal hari ini. Saat diproses, sistem menghitung hari terlambat dan denda, mengubah status menjadi dikembalikan, serta menambah stok tersedia sebanyak satu."

    Add-Paragraph $selection "4.8 Hasil Perbaikan Dialog Perpanjangan" $wdStyleHeading2
    Add-Paragraph $selection "Dialog perpanjangan menggunakan elemen HTML dialog dan Blade biasa, bukan React. Form dibuat grid vertikal dengan lebar maksimum 620 px dan batas responsif calc(100vw - 32px). Aturan ini mencegah benturan dengan CSS form pada kolom aksi tabel dan menghilangkan scrollbar horizontal."
    Add-Image $document $selection $ModalImagePath 440
    Add-Paragraph $selection "Gambar 4.1 Dialog perpanjangan setelah diperbaiki pada viewport desktop." $wdStyleNormal $wdAlignCenter 12 -Italic -FontSize 9

    Add-Paragraph $selection "4.9 Rekapitulasi Data" $wdStyleHeading2
    Add-Paragraph $selection "Dashboard dan modul rekap mengambil dokumen MongoDB lalu menjalankan agregasi pada Laravel Collection. Pendekatan ini tetap menggunakan sumber data MongoDB dan mudah dipresentasikan karena setiap operasi dapat ditunjukkan dari query hingga hasil."
    Add-Table $document $selection @("Operasi", "Contoh di SHELFY") @(
        @("COUNT", "Jumlah judul, anggota, transaksi, buku dipinjam, dan buku terlambat."),
        @("SUM", "Total stok, stok tersedia, total transaksi, dan total denda."),
        @("AVG", "Rata-rata stok per judul dan rata-rata denda/peminjaman."),
        @("Greater Than", "Buku dengan stok atau popularitas di atas batas tertentu; pinjaman dengan denda > 0."),
        @("GROUP BY", "Pengelompokan buku per kategori dan jumlah transaksi per buku."),
        @("SORT DESC", "Menentukan buku populer/terlaris berdasarkan frekuensi peminjaman.")
    ) @(105, 363) | Out-Null
    Add-CodeBlock $document $selection @'
$totalStock = $books->sum('stok_total');
$averageStock = $books->avg('stok_total');
$lateLoans = $loans->filter(fn ($loan) => $loan->status === 'terlambat');
$byCategory = $books->groupBy('kategori')->map->count();
'@

    Add-Paragraph $selection "4.10 Cara Kerja Nota PDF" $wdStyleHeading2
    Add-NumberedSteps $selection @(
        "ReceiptController mengambil Loan yang sudah dikembalikan dan memeriksa role atau kepemilikan mahasiswa.",
        "Controller mengirim data buku, anggota, tanggal, denda, pembayaran, dan petugas ke Blade nota.",
        "Blade menampilkan layout nota formal dengan CSS khusus @media print.",
        "Tombol Cetak PDF menjalankan window.print().",
        "Pada dialog printer, pengguna memilih Save as PDF atau Microsoft Print to PDF.",
        "CSS print menyembunyikan sidebar/tombol dan mengatur ukuran, margin, serta pemisahan halaman agar hasil rapi."
    )
    Add-Paragraph $selection "Implementasi saat ini menggunakan mesin cetak browser, bukan memanggil Dompdf dari controller. Hal ini tetap menghasilkan berkas PDF dan mudah didemonstrasikan tanpa layanan eksternal."

    Add-Paragraph $selection "4.11 Storage Link" $wdStyleHeading2
    Add-Paragraph $selection "File upload tidak disimpan di folder source code public secara langsung. Laravel menyimpan cover di storage/app/public/covers dan foto profil petugas di storage/app/public/profile-photos. Perintah berikut membuat symbolic link public/storage agar browser dapat mengakses file tersebut."
    Add-CodeBlock $document $selection @'
php artisan storage:link

# Contoh hasil akses:
public/storage/covers/nama-file.jpg
public/storage/profile-photos/nama-file.jpg
'@

    Add-Paragraph $selection "4.12 Pengujian" $wdStyleHeading2
    Add-Bullets $selection @(
        "Feature test autentikasi memastikan register tidak auto-login dan login mengarah sesuai role.",
        "Feature test SHELFY memeriksa CRUD buku, keranjang/checkout, pengambilan, tanggal kembali manual, perpanjangan, denda, dan akses role.",
        "php artisan route:list digunakan untuk memeriksa route aplikasi.",
        "npm run build memastikan CSS/JavaScript dapat dikompilasi Vite.",
        "Browser QA memeriksa katalog lima kolom serta dialog perpanjangan pada desktop dan ponsel."
    )

    $selection.InsertBreak($wdPageBreak)
    Add-Paragraph $selection "BAB V" $wdStyleHeading1 $wdAlignCenter 0
    Add-Paragraph $selection "PENUTUP" $wdStyleHeading1 $wdAlignCenter 12
    Add-Paragraph $selection "5.1 Kesimpulan" $wdStyleHeading2
    Add-Paragraph $selection "SHELFY telah menerapkan aplikasi perpustakaan berbasis MongoDB dengan arsitektur Laravel MVC. Fitur utama meliputi autentikasi tiga role, CRUD, pencarian/filter, keranjang dan checkout, konfirmasi pengambilan, perpanjangan manual, pengembalian manual, denda, nota PDF, serta rekapitulasi COUNT, SUM, AVG, greater-than, grouping, dan popularitas. Pemisahan users dan members menjaga data akun tetap terpisah dari data operasional anggota."
    Add-Paragraph $selection "5.2 Saran Pengembangan" $wdStyleHeading2
    Add-Bullets $selection @(
        "Menambahkan notifikasi email sebelum jatuh tempo.",
        "Menggunakan transaksi MongoDB untuk operasi stok dan loan yang lebih kuat pada beban tinggi.",
        "Menambahkan audit log perubahan data penting.",
        "Mengintegrasikan generator PDF server-side bila nota perlu dibuat otomatis tanpa dialog printer.",
        "Menambahkan backup terjadwal dan autentikasi MongoDB untuk lingkungan produksi."
    )

    $selection.InsertBreak($wdPageBreak)
    Add-Paragraph $selection "LAMPIRAN A" $wdStyleHeading1 $wdAlignCenter 0
    Add-Paragraph $selection "PEMBAGIAN TUGAS KELOMPOK" $wdStyleHeading1 $wdAlignCenter 12
    Add-Paragraph $selection "Pembagian dibuat berdasarkan modul, tetapi setiap anggota tetap memahami alur keseluruhan dan ikut melakukan integrasi serta pengujian."
    Add-Table $document $selection @("Anggota", "Tanggung Jawab Utama", "Bagian Presentasi") @(
        @("Michael Eluzai Situmorang", "Koordinator; autentikasi Breeze; model User/Member; role middleware; profil; integrasi akhir.", "Latar belakang, tujuan, arsitektur MVC, login/register, dan perbedaan users-members."),
        @("Muhammad Anantha Mahardika Ridwan", "Model/CRUD Book; cover dan storage link; katalog; pencarian/filter; keranjang dan checkout.", "Demo buku, upload cover, filter, detail katalog, keranjang, dan checkout."),
        @("Muhammad Fadhil Athallah", "Loan; bukti pengambilan; perpanjangan; pengembalian manual; denda; pembayaran; nota PDF.", "Demo alur transaksi dari pengambilan sampai nota PDF dan penjelasan controller terkait."),
        @("Mumpuni Nur Idzati Ayuningtyas", "Konfigurasi MongoDB/Compass; seeder; dashboard; rekap agregasi; testing dan dokumentasi.", "Database NoSQL, desain collection, SUM/AVG/Greater Than, seeder, serta hasil pengujian.")
    ) @(118, 210, 140) | Out-Null

    Add-Paragraph $selection "Aturan Kolaborasi" $wdStyleHeading2
    Add-Bullets $selection @(
        "Setiap anggota melakukan minimal satu commit atau pencatatan perubahan modulnya.",
        "Perubahan model/field didiskusikan karena dapat memengaruhi controller dan Blade anggota lain.",
        "Sebelum presentasi, semua anggota mencoba login sebagai tiga role dan menjalankan satu alur transaksi penuh.",
        "Setiap anggota mampu menjelaskan mengapa aplikasi menggunakan MongoDB, bukan MySQL."
    )

    $selection.InsertBreak($wdPageBreak)
    Add-Paragraph $selection "LAMPIRAN B" $wdStyleHeading1 $wdAlignCenter 0
    Add-Paragraph $selection "PANDUAN PRESENTASI BACKEND" $wdStyleHeading1 $wdAlignCenter 12
    Add-Paragraph $selection "Urutan Demo yang Disarankan" $wdStyleHeading2
    Add-NumberedSteps $selection @(
        "Tunjukkan .env dan MongoDB Compass pada database shelfy_db.",
        "Buka users dan members, lalu jelaskan perbedaan data login dan profil anggota.",
        "Tunjukkan model MongoDB dan field fillable/casts.",
        "Tunjukkan route, middleware role, dan satu controller dari request sampai model.",
        "Login sebagai mahasiswa, cari buku, masukkan keranjang, dan checkout.",
        "Login sebagai pustakawan, konfirmasi pengambilan, perpanjang tanggal, lalu kembalikan dengan tanggal manual.",
        "Cetak nota PDF dan jelaskan @media print serta window.print().",
        "Tutup dengan dashboard rekap SUM, AVG, Greater Than, kategori, dan buku populer."
    )

    Add-Paragraph $selection "Pertanyaan yang Mungkin Diajukan" $wdStyleHeading2
    Add-Table $document $selection @("Pertanyaan", "Jawaban Singkat") @(
        @("Mengapa users dan members dipisah?", "users khusus keamanan/login/role; members khusus data keanggotaan. Staff tidak harus menjadi anggota peminjam."),
        @("Di mana bukti bahwa ini MongoDB?", "Model mewarisi class MongoDB Laravel, koneksi default mongodb, _id ObjectId, dan data terlihat sebagai document pada Compass."),
        @("Apa kompleksitas databasenya?", "Reference antarcollection, denormalisasi snapshot transaksi, embedded history, agregasi, status workflow, dan kontrol akses."),
        @("Bagaimana stok tetap benar?", "Checkout mengurangi stok; pengembalian menambah stok; validasi mencegah stok negatif dan penghapusan buku aktif."),
        @("Bagaimana PDF dibuat?", "Controller menyiapkan data, Blade merender nota, CSS print merapikan, dan browser menyimpan hasil sebagai PDF."),
        @("Apakah password tersimpan biasa?", "Tidak. Model User memakai cast hashed sehingga MongoDB menyimpan hash, bukan password asli.")
    ) @(185, 283) | Out-Null

    Add-Paragraph $selection "Perintah Persiapan Demo" $wdStyleHeading2
    Add-CodeBlock $document $selection @'
composer install
npm install
php artisan storage:link
php artisan shelfy:fresh
npm run build
php artisan serve

# Pengujian
php artisan route:list
php artisan test
'@

    Add-Paragraph $selection "Catatan: php artisan shelfy:fresh menghapus data aplikasi SHELFY dan mengisi ulang data demo. Jalankan hanya ketika data lama memang boleh dibersihkan."

    $document.TablesOfContents.Item(1).Update()
    $document.Fields.Update() | Out-Null
    $document.Save()
    $document.ExportAsFixedFormat($PdfPath, $wdExportFormatPDF)
    Write-Output "DOCX=$OutputPath"
    Write-Output "PDF=$PdfPath"
    Write-Output "PAGES=$($document.ComputeStatistics(2))"
}
finally {
    if ($null -ne $document) {
        $document.Close($false)
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($document) | Out-Null
    }
    if ($null -ne $word) {
        $word.Quit()
        [System.Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
