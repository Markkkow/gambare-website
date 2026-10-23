import { json, checkPublicKey, supabaseRest, cleanText } from '../lib/supabase.js';

const PERIODS = ['Per Minggu', 'Per Bulan', '-'];

// Ambil field barang dari input (dipakai untuk create & update)
function itemFields(input) {
  const need = Number(input.need) || 0;
  const period = PERIODS.includes(input.period) ? input.period : '-';
  return {
    name: cleanText(input.name),
    unit: cleanText(input.unit),
    supplier: cleanText(input.supplier) || '-',
    brand: cleanText(input.brand),
    period,
    min_stock: need
  };
}

async function stockItems(request) {
  try {
    if (request.method === 'GET') {
      if (!checkPublicKey(request)) return json({ success: false, message: 'API key tidak valid.' }, 401);
      const rows = await supabaseRest('stock_levels?select=id,name,unit,min_stock,supplier,brand,period,stock&order=supplier.asc,name.asc');
      const items = (rows || []).map(r => ({
        id: r.id, name: r.name, unit: r.unit, supplier: r.supplier || '-', brand: r.brand || '',
        period: r.period || '-', need: Number(r.min_stock) || 0, stock: Number(r.stock) || 0
      }));
      return json({ success: true, items });
    }

    if (request.method !== 'POST') return json({ success: false, message: 'Method tidak diizinkan.' }, 405);

    let input;
    try { input = await request.json(); }
    catch { return json({ success: false, message: 'Data JSON tidak valid.' }, 400); }
    if (!checkPublicKey(request, input)) return json({ success: false, message: 'API key tidak valid.' }, 401);

    if (input.action === 'delete') {
      if (!input.id) return json({ success: false, message: 'ID barang wajib diisi.' }, 400);
      await supabaseRest(`stock_items?id=eq.${encodeURIComponent(input.id)}`, {
        method: 'DELETE', headers: { Prefer: 'return=minimal' }
      });
      return json({ success: true });
    }

    const fields = itemFields(input);
    if (!fields.name || !fields.unit) return json({ success: false, message: 'Nama barang dan satuan wajib diisi.' }, 400);
    if (fields.min_stock < 0) return json({ success: false, message: 'Kebutuhan tidak boleh negatif.' }, 400);

    const dupe = await supabaseRest(`stock_items?name=ilike.${encodeURIComponent(fields.name)}&select=id`);
    if (dupe?.some(d => d.id !== input.id)) return json({ success: false, message: `Barang "${fields.name}" sudah ada.` }, 409);

    if (input.action === 'update') {
      if (!input.id) return json({ success: false, message: 'ID barang wajib diisi.' }, 400);
      await supabaseRest(`stock_items?id=eq.${encodeURIComponent(input.id)}`, {
        method: 'PATCH', headers: { Prefer: 'return=minimal' }, body: JSON.stringify(fields)
      });
      return json({ success: true, id: input.id });
    }

    // action: create
    const initial = Number(input.initial) || 0;
    if (initial < 0) return json({ success: false, message: 'Stok awal tidak boleh negatif.' }, 400);
    const inserted = await supabaseRest('stock_items', {
      method: 'POST', headers: { Prefer: 'return=representation' }, body: JSON.stringify([fields])
    });
    const id = inserted?.[0]?.id;
    if (id && initial > 0) {
      await supabaseRest('stock_movements', {
        method: 'POST', headers: { Prefer: 'return=minimal' },
        body: JSON.stringify([{ item_id: id, type: 'in', qty: initial, note: 'Stok awal' }])
      });
    }
    return json({ success: true, id });
  } catch (err) {
    console.error(err);
    return json({ success: false, message: err.message || 'Gagal memproses data barang.' }, 500);
  }
}

export default { fetch: stockItems };
