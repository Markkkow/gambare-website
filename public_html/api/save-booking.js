import { json, checkPublicKey, supabaseRest, validDate, cleanPhone, cleanText } from '../lib/supabase.js';

const TOTAL_TABLES = 15;

async function saveBooking(request) {
  if (request.method !== 'POST') return json({ success: false, message: 'Method tidak diizinkan.' }, 405);
  try {
    let input;
    try { input = await request.json(); }
    catch { return json({ success: false, message: 'Data JSON tidak valid.' }, 400); }

    if (!checkPublicKey(request, input)) return json({ success: false, message: 'API key tidak valid.' }, 401);

    for (const field of ['name', 'phone', 'date', 'time', 'floor', 'tables', 'guests']) {
      if (input[field] === undefined || input[field] === null || input[field] === '') {
        return json({ success: false, message: `Field '${field}' wajib diisi.` }, 400);
      }
    }
    if (!validDate(input.date)) return json({ success: false, message: 'Format tanggal tidak valid.' }, 400);

    const tables = Number.parseInt(input.tables, 10);
    const guests = Number.parseInt(input.guests, 10);
    if (!Number.isInteger(tables) || tables < 1 || tables > TOTAL_TABLES)
      return json({ success: false, message: 'Jumlah meja tidak valid.' }, 400);
    if (!Number.isInteger(guests) || guests < 1)
      return json({ success: false, message: 'Jumlah tamu tidak valid.' }, 400);

    const existing = await supabaseRest(`bookings?date=eq.${encodeURIComponent(input.date)}&select=tables`);
    const booked = (existing || []).reduce((sum, b) => sum + (Number(b.tables) || 0), 0);
    if (booked + tables > TOTAL_TABLES)
      return json({ success: false, message: `Meja tidak cukup. Sisa: ${Math.max(0, TOTAL_TABLES - booked)}` }, 409);

    const payload = [{
      name: cleanText(input.name), phone: cleanPhone(input.phone), date: input.date,
      time: input.time, floor: cleanText(input.floor), tables, guests,
      notes: cleanText(input.notes || '')
    }];

    const inserted = await supabaseRest('bookings', {
      method: 'POST', headers: { Prefer: 'return=representation' }, body: JSON.stringify(payload)
    });
    return json({ success: true, id: inserted?.[0]?.id || null });
  } catch (err) {
    console.error(err);
    return json({ success: false, message: err.message || 'Gagal menyimpan booking.' }, 500);
  }
}

export default { fetch: saveBooking };
