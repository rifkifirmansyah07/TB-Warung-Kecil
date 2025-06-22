# Warung Kecil - Point of Sale System

Sistem Point of Sale (POS) sederhana untuk warung kecil yang dibuat dengan PHP dan MySQL.

## 🎯 Fitur Utama

- **Dashboard** - Overview statistik penjualan
- **Manajemen Produk** - CRUD produk dengan kategori
- **Manajemen Kategori** - Organisasi produk berdasarkan kategori
- **Transaksi Penjualan** - Interface POS untuk kasir
- **Riwayat Transaksi** - History transaksi dengan filter dan search
- **Laporan Penjualan** - Analytics harian, produk terlaris, per kategori
- **Cetak Struk** - Receipt printing untuk customer

## 🛠️ Teknologi

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, CSS3, JavaScript (Vanilla)
- **Icons**: Font Awesome
- **Server**: Apache/Nginx

## 📋 Persyaratan Sistem

- PHP 7.4 atau lebih baru
- MySQL 5.7 atau lebih baru
- Apache/Nginx web server
- PDO MySQL extension

## 🚀 Instalasi

1. **Clone repository**
   ```bash
   git clone https://github.com/mohyasrul/warung-kecil.git
   cd warung-kecil
   ```

2. **Setup Database**
   - Buat database baru di MySQL
   - Import file `wr_ripki.sql` ke database
   ```sql
   mysql -u username -p database_name < wr_ripki.sql
   ```

3. **Konfigurasi Database**
   - Edit file `config/database.php`
   - Sesuaikan konfigurasi database:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_PORT', '3306');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Setup Web Server**
   - Letakkan folder project di document root
   - Atau jalankan PHP built-in server:
   ```bash
   php -S localhost:8000
   ```

5. **Login**
   - Buka browser ke `http://localhost:8000`
   - Login dengan:
     - Username: `owner`
     - Password: `admin123`

## 📁 Struktur Project

```
warung-kecil/
├── assets/
│   └── css/
│       └── style.css          # Main stylesheet
├── config/
│   └── database.php           # Database configuration
├── includes/
│   ├── auth.php              # Authentication class
│   └── layout.php            # Layout functions
├── wr_ripki.sql              # Database schema
├── dashboard.php             # Main dashboard
├── login.php                 # Login page
├── transaksi.php             # POS interface
├── produk.php                # Product management
├── kategori.php              # Category management
├── riwayat-transaksi.php     # Transaction history
├── laporan-penjualan.php     # Sales reports
└── README.md
```

## 💡 Penggunaan

### 1. Dashboard
- Melihat statistik penjualan hari ini
- Quick access ke fitur utama
- Monitoring stok produk

### 2. Manajemen Produk
- Tambah/edit/hapus produk
- Set harga beli dan jual
- Manajemen stok dengan log
- Kategorisasi produk

### 3. Transaksi Penjualan (POS)
- Interface kasir yang user-friendly
- Search produk dengan cepat
- Calculate total otomatis
- Print struk transaksi

### 4. Laporan
- Laporan harian dengan trend
- Produk terlaris dengan ranking
- Analisis per kategori
- Export dan print laporan

## 🔐 Keamanan

- Password hashing dengan bcrypt
- Session management
- SQL injection protection dengan prepared statements
- Login attempt limiting
- User access control

## 🎨 Screenshot

*Screenshot akan ditambahkan setelah deployment*

## 🤝 Kontribusi

1. Fork repository ini
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit perubahan (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buat Pull Request

## 📝 License

Project ini menggunakan MIT License. Lihat file `LICENSE` untuk detail.

## 👨‍💻 Developer

**Moh Yasrul**
- GitHub: [@mohyasrul](https://github.com/mohyasrul)

## 📞 Support

Jika ada pertanyaan atau bug, silakan buat issue di repository ini.

---

⭐ **Star repository ini jika bermanfaat!**
