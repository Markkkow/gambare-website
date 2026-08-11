# Gambare House — Struktur Deploy

Ini struktur folder yang benar untuk di-upload ke hosting (harus support PHP):

```
public_html/                  (atau folder root domain kamu)
├── index.html                 ← halaman customer
├── kasir.html                 ← dashboard kasir (dikunci PIN)
├── config.js                  ← API_KEY (publik, aman dilihat customer)
├── kasir-config.js            ← PIN kasir (JANGAN link dari index.html)
├── images/
│   ├── logo/
│   │   └── Untitled_Artwork-1.png     ← logo di navbar
│   ├── hero/
│   │   ├── 2A2A5992.jpg                ← foto slideshow background hero
│   │   ├── 2A2A6006.jpg
│   │   ├── BEN00019.jpg
│   │   └── BEN05942.jpg
│   └── decor/
│       └── pngwing.com.png             ← elemen dekorasi awan (parallax)
├── files/
│   └── menu-gambare-house.pdf          ← PDF menu (ditampilkan & bisa didownload di section Menu)
├── api/
│   ├── _helpers.php
│   ├── checkout_booking.php
│   ├── get_availability.php
│   ├── get_bookings.php
│   └── save_booking.php
└── data/
    ├── bookings.json
    └── .htaccess               ← blokir akses langsung ke bookings.json
```

## Cara isi folder `images/`

Semua path gambar di `index.html` sudah diarahkan ke folder di atas, jadi tinggal taruh file dengan **nama persis sama** ke folder yang sesuai:

- `images/logo/Untitled_Artwork-1.png`
- `images/hero/2A2A5992.jpg`, `2A2A6006.jpg`, `BEN00019.jpg`, `BEN05942.jpg`
- `images/decor/pngwing.com.png`

File PDF menu juga sama, taruh di:

- `files/menu-gambare-house.pdf`

(Sebelumnya nama filenya `preview gambare.pdf` dengan spasi dan ditaruh di root — sudah diganti nama tanpa spasi dan dipindah ke folder `files/` biar rapi & aman untuk URL.)

Kalau nanti mau tambah foto galeri/menu lain, taruh juga di dalam `images/` (boleh bikin subfolder baru misalnya `images/menu/`). Kalau mau tambah PDF lain (misal menu minuman terpisah), taruh juga di `files/`. Jangan ditaruh sejajar dengan `index.html` di folder root lagi, biar rapi.

## Yang sudah diperbaiki (histori)

1. **PIN kasir bocor ke customer** — sudah dipisah, `config.js` cuma berisi `API_KEY`, `KASIR_PIN` dipindah ke `kasir-config.js` yang hanya di-load oleh `kasir.html`.
2. **`.htaccess`** — diberi nama benar (dengan titik di depan), ditaruh di `data/`, dan ditambah fallback untuk Apache 2.2 selain 2.4.
3. **Struktur folder dirapikan** sesuai path yang dipakai kode PHP dan HTML.
4. **`API_KEY` & `KASIR_PIN`** — sudah diganti dari nilai default ke nilai acak baru (lihat pesan sebelumnya untuk nilai PIN-nya, atau cek langsung isi `kasir-config.js`).
5. **Data testing di `bookings.json`** sudah dikosongkan (`[]`).
6. **Semua emoji dihapus** dari `index.html` dan `kasir.html` (ikon informasi di section booking diganti SVG garis minimalis, bukan emoji).
7. **Folder `images/` dibuat** supaya semua file gambar tidak tersebar acak di root — lihat struktur di atas.
8. **Folder `files/` dibuat** untuk PDF menu, nama file dirapikan dari `preview gambare.pdf` (ada spasi) jadi `menu-gambare-house.pdf`.

## Yang masih perlu kamu lengkapi

- **Upload file foto asli** ke folder `images/` sesuai struktur di atas (file-nya sendiri belum pernah di-upload ke saya, jadi belum ada isinya).
- **Upload file PDF menu** sebagai `files/menu-gambare-house.pdf`.
- Pastikan hosting kamu **support PHP** dan folder `data/` **writable** (permission tulis) supaya `bookings.json` bisa diupdate.
