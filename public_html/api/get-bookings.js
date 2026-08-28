import { json, checkPublicKey, supabaseRest, validDate } from '../lib/supabase.js';

async function getBookings(request) {
  try {
    if (!checkPublicKey(request)) return json({ success: false, message: 'API key tidak valid.' }, 401);

    const url = new URL(request.url);
    const date = url.searchParams.get('date');
    if (!validDate(date)) return json({ success: false, message: 'Parameter tanggal tidak valid.' }, 400);

    const rows = await supabaseRest(
      `bookings?date=eq.${encodeURIComponent(date)}&select=id,name,phone,date,time,floor,tables,guests,notes,created_at&order=time.asc`
    );

    const bookings = (rows || []).map(b => ({
      id: b.id, name: b.name, phone: b.phone, date: b.date,
      time: String(b.time || '').slice(0, 5), floor: b.floor, tables: b.tables,
      guests: b.guests, notes: b.notes || '', status: 'pending', createdAt: b.created_at
    }));

    return json({ success: true, date, bookings });
  } catch (err) {
    console.error(err);
    return json({ success: false, message: err.message || 'Gagal memuat booking.' }, 500);
  }
}

export default { fetch: getBookings };
