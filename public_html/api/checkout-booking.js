import { json, checkPublicKey, supabaseRest } from '../lib/supabase.js';

export async function POST(request) {
  try {
    let input;
    try { input = await request.json(); }
    catch { return json({ success: false, message: 'Data JSON tidak valid.' }, 400); }

    if (!checkPublicKey(request, input)) return json({ success: false, message: 'API key tidak valid.' }, 401);
    if (!input.id) return json({ success: false, message: 'ID booking wajib diisi.' }, 400);

    await supabaseRest(`bookings?id=eq.${encodeURIComponent(input.id)}`, {
      method: 'DELETE',
      headers: { Prefer: 'return=minimal' }
    });

    return json({ success: true });
  } catch (err) {
    console.error(err);
    return json({ success: false, message: err.message || 'Gagal menghapus booking.' }, 500);
  }
}
