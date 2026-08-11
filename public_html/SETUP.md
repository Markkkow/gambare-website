# Gambare House — Vercel + Supabase Setup

## 1. Buat project Supabase
Buat project Supabase Free, lalu buka SQL Editor dan jalankan isi `supabase.sql`.

## 2. Ambil Supabase URL dan Secret Key
Di Supabase Dashboard buka Project Settings / API Keys (atau Connect).
Gunakan **Secret key** (`sb_secret_...`) untuk backend. Jangan masukkan secret key ke index.html, config.js, atau kasir.html.

## 3. Tambahkan Environment Variables di Vercel
Project Vercel > Settings > Environment Variables:

- `SUPABASE_URL` = URL project Supabase, misalnya `https://xxxx.supabase.co`
- `SUPABASE_SECRET_KEY` = secret key Supabase (`sb_secret_...`)
- `PUBLIC_API_KEY` = `40deb31a-eeec-4fe6-82b9-dd9493a4ec3d`

Aktifkan untuk Production, Preview, dan Development bila perlu.

## 4. Upload / push file
Gunakan struktur:

```
/
├── index.html
├── kasir.html
├── config.js
├── kasir-config.js
├── api/
│   ├── get-bookings.js
│   ├── get-availability.js
│   ├── save-booking.js
│   └── checkout-booking.js
├── lib/
│   └── supabase.js
└── ... folder images/files milik project lama
```

File PHP lama (`api/*.php`) dan folder `data/bookings.json` tidak diperlukan lagi.

## 5. Redeploy
Setelah Environment Variables disimpan, lakukan redeploy.

## 6. Tes endpoint
Buka:

`https://DOMAIN-KAMU.vercel.app/api/get-bookings?date=2026-08-11&key=40deb31a-eeec-4fe6-82b9-dd9493a4ec3d`

Jika benar, hasilnya:

```json
{"success":true,"date":"2026-08-11","bookings":[]}
```

## Perilaku checkout
Saat kasir menekan `Tandai Selesai`, endpoint `checkout-booking` menjalankan DELETE di Supabase. Booking hilang dari dashboard dan meja langsung kembali tersedia.
