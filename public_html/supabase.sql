create extension if not exists pgcrypto;

create table if not exists public.bookings (
    id uuid primary key default gen_random_uuid(),
    name text not null,
    phone text not null,
    date date not null,
    time time not null,
    floor text not null,
    tables integer not null check (tables between 1 and 15),
    guests integer not null check (guests >= 1),
    notes text not null default '',
    created_at timestamptz not null default now()
);

create index if not exists bookings_date_idx on public.bookings(date);
create index if not exists bookings_date_time_idx on public.bookings(date, time);

alter table public.bookings enable row level security;
revoke all on table public.bookings from anon, authenticated;

-- ─── STOK KITCHEN ───
create table if not exists public.stock_items (
    id uuid primary key default gen_random_uuid(),
    name text not null unique,
    category text not null default 'Lainnya',
    unit text not null,
    min_stock numeric not null default 0 check (min_stock >= 0),
    created_at timestamptz not null default now()
);

create table if not exists public.stock_movements (
    id uuid primary key default gen_random_uuid(),
    item_id uuid not null references public.stock_items(id) on delete cascade,
    type text not null check (type in ('in', 'out')),
    qty numeric not null check (qty > 0),
    note text not null default '',
    created_at timestamptz not null default now()
);

create index if not exists stock_movements_item_idx on public.stock_movements(item_id);
create index if not exists stock_movements_created_idx on public.stock_movements(created_at);

-- Kolom tambahan sesuai sheet "Daftar Rekap Barang"
-- supplier = Kode Barang, min_stock = Kebutuhan, period = Ket. Kebutuhan, brand = Merk
alter table public.stock_items add column if not exists supplier text not null default '-';
alter table public.stock_items add column if not exists brand text not null default '';
alter table public.stock_items add column if not exists period text not null default '-';

-- Stok saat ini = total masuk - total keluar
drop view if exists public.stock_levels;
create view public.stock_levels as
select i.id, i.name, i.category, i.unit, i.min_stock, i.supplier, i.brand, i.period, i.created_at,
       coalesce(sum(case when m.type = 'in' then m.qty else -m.qty end), 0) as stock
from public.stock_items i
left join public.stock_movements m on m.item_id = i.id
group by i.id;

-- Data awal dari 10_2026_KITCHEN_SO_GAMBARE.xlsx (stok awal kosong)
insert into public.stock_items (name, supplier, unit, min_stock, period, brand) values
    ('Bawang Goreng', 'Online', 'Bungkus', 4, 'Per Minggu', ''),
    ('Cabai Bubuk', 'Online', 'Kg', 1, 'Per Minggu', ''),
    ('Cabai Kering', 'Online', 'Kg', 5, 'Per Minggu', ''),
    ('Gas Portabel', 'Indomaret', 'Pcs', 2, '-', ''),
    ('Jamur Kuping', 'Cici', 'Bal', 2, 'Per Minggu', ''),
    ('Jamur Shitake', 'Cici', 'Ons', 2, 'Per Minggu', ''),
    ('Katsubushi', 'Mr Ben', 'Kg', 1, 'Per Bulan', ''),
    ('Kecap', 'Indogrosir', 'Bungkus', 8, 'Per Minggu', 'Bango'),
    ('Kewpie', 'Mr Ben', 'Pcs', 3, 'Per Bulan', ''),
    ('Lada', 'Pajak', 'gr', 2, 'Per Minggu', ''),
    ('Magiblock', 'Indogrosir', 'Pcs', 10, 'Per Minggu', ''),
    ('Mayones', 'Online', 'Pcs', 10, 'Per Bulan', ''),
    ('Minyak Goreng', 'Indogrosir', 'Liter', 42, 'Per Minggu', ''),
    ('Minyak Wijen', 'Kemang', 'Botol', 5, 'Per Minggu', ''),
    ('Nori', 'Mr Ben', 'Bungkus', 5, 'Per Minggu', ''),
    ('Nori Halus', 'Kemang', 'Bungkus', 3, 'Per Minggu', ''),
    ('Okonomiyaki', 'Mr Ben', 'Botol', 2, '-', ''),
    ('Plastik Asoy 2 Kg', 'Toko', 'Bungkus', 2, '-', ''),
    ('Plastik Asoy 3 Kg', 'Toko', 'Bungkus', 2, '-', ''),
    ('Plastik Asoy 5 Kg', 'Toko', 'Bungkus', 2, '-', ''),
    ('Plastik PE 0.5 Kg', 'Toko', 'Bungkus', 2, '-', ''),
    ('Plastik PE 5 Kg', 'Toko', 'Bungkus', 2, '-', ''),
    ('Royco', 'Indogrosir', 'Bungkus', 7, 'Per Minggu', ''),
    ('Sabun', 'Indogrosir', 'Renceng', 2, '-', ''),
    ('Saus Hot Lava', 'Online', 'Bungkus', 4, 'Per Minggu', ''),
    ('Saus Sambal', 'Indogrosir', 'Bungkus', 21, 'Per Minggu', 'Sasa'),
    ('Saus Teriyaki', 'Indogrosir', 'Botol', 5, 'Per Minggu', 'Saori'),
    ('Saus Tiram', 'Indogrosir', 'Botol', 7, 'Per Minggu', 'Saori'),
    ('Saus Tomat', 'Indogrosir', 'Bungkus', 3, 'Per Minggu', 'ABC'),
    ('Sendok', 'Toko', 'Bungkus', 4, '-', ''),
    ('Sumpit', 'Toko', 'Bungkus', 4, '-', ''),
    ('Susu', 'Online', 'Pcs', 4, 'Per Minggu', ''),
    ('Tepung', 'Indogrosir', 'Kg', 15, 'Per Minggu', ''),
    ('Tepung Panko', 'Mr Ben', 'Kg', 10, 'Per Minggu', ''),
    ('Saus Nanban', 'Online', 'Botol', 1, '-', ''),
    ('Totole', 'Indogrosir', 'Bungkus', 15, 'Per Minggu', ''),
    ('Truffle', 'Mr Ben', 'Kotak', 1, 'Per Bulan', ''),
    ('Demi Glace', 'Online', 'Kotak', 1, '-', ''),
    ('Wakame Dry', 'Mr Ben', 'Bungkus', 4, 'Per Minggu', ''),
    ('T.A Mentai', 'Online', 'Plastik', 4, '-', ''),
    ('T.A Saus', 'Toko', 'Plastik', 4, '-', ''),
    ('T.A Kotak', 'Online', 'Pack', 4, '-', ''),
    ('T.A Reguler', 'Toko', 'Plastik', 4, '-', ''),
    ('T.A Premium', 'Toko', 'Plastik', 4, '-', ''),
    ('Kertas Kentang', 'Online', 'Bungkus', 4, '-', ''),
    ('Karet', 'Toko', 'Bungkus', 4, '-', ''),
    ('Kabel Ties', 'Toko', 'Bungkus', 4, '-', ''),
    ('Ayam', '-', 'Kg', 4, '-', ''),
    ('Ceker', '-', 'Kg', 4, '-', '')
on conflict (name) do nothing;

alter table public.stock_items enable row level security;
alter table public.stock_movements enable row level security;
revoke all on table public.stock_items from anon, authenticated;
revoke all on table public.stock_movements from anon, authenticated;
revoke all on table public.stock_levels from anon, authenticated;
