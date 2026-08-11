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
