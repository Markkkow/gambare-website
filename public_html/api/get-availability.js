import { json, checkPublicKey, supabaseRest, validDate } from '../lib/supabase.js';

const TOTAL_TABLES = 15;

export async function GET(request) {
  try {
    if (!checkPublicKey(request)) return json({ success: false, message: 'API key tidak valid.' }, 401);

    const url = new URL(request.url);
    const date = url.searchParams.get('date');
    if (!validDate(date)) return json({ success: false, message: 'Parameter tanggal tidak valid.' }, 400);

    const rows = await supabaseRest(
      `bookings?date=eq.${encodeURIComponent(date)}&select=tables`
    );
    const booked = (rows || []).reduce((sum, b) => sum + (Number(b.tables) || 0), 0);

    return json({
      success: true,
      date,
      total: TOTAL_TABLES,
      booked,
      available: Math.max(0, TOTAL_TABLES - booked)
    });
  } catch (err) {
    console.error(err);
    return json({ success: false, message: err.message || 'Gagal mengecek ketersediaan.' }, 500);
  }
}
