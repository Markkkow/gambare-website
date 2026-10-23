import { json, checkPublicKey, supabaseRest, validDate, cleanText } from '../lib/supabase.js';

// Jam operasional di Medan → tanggal dihitung dalam WIB (UTC+7)
const TZ = '+07:00';

function nextDay(date) {
  const d = new Date(date + 'T00:00:00Z');
  d.setUTCDate(d.getUTCDate() + 1);
  return d.toISOString().slice(0, 10);
}

async function stockMovements(request) {
  try {
    if (request.method === 'GET') {
      if (!checkPublicKey(request)) return json({ success: false, message: 'API key tidak valid.' }, 401);
      const url = new URL(request.url);
      const from = url.searchParams.get('from');
      const to = url.searchParams.get('to') || from;
      if (!validDate(from) || !validDate(to)) return json({ success: false, message: 'Parameter tanggal tidak valid.' }, 400);

      const gte = encodeURIComponent(`${from}T00:00:00${TZ}`);
      const lt = encodeURIComponent(`${nextDay(to)}T00:00:00${TZ}`);

      // Rekap per barang (format sheet "Daftar Rekap Barang"):
      // Stok Awal = saldo sebelum `from`, Masuk/Keluar = selama periode, Akhir = Awal + Masuk - Keluar
      if (url.searchParams.get('rekap') === '1') {
        const [items, moves] = await Promise.all([
          supabaseRest('stock_items?select=id,name,unit,min_stock,supplier,brand,period&order=supplier.asc,name.asc'),
          supabaseRest(`stock_movements?select=item_id,type,qty,created_at&created_at=lt.${lt}`)
        ]);
        const start = new Date(`${from}T00:00:00${TZ}`).getTime();
        const acc = {};
        for (const m of moves || []) {
          const a = acc[m.item_id] ||= { open: 0, in: 0, out: 0 };
          const q = Number(m.qty) || 0, sign = m.type === 'in' ? 1 : -1;
          if (new Date(m.created_at).getTime() < start) a.open += sign * q;
          else a[m.type] += q;
        }
        const rekap = (items || []).map(i => {
          const a = acc[i.id] || { open: 0, in: 0, out: 0 };
          const close = a.open + a.in - a.out, need = Number(i.min_stock) || 0;
          return {
            id: i.id, name: i.name, unit: i.unit, supplier: i.supplier || '-', brand: i.brand || '', period: i.period || '-',
            open: a.open, in: a.in, out: a.out, close, need,
            excess: Math.max(0, close - need), buy: Math.max(0, need - close)
          };
        });
        return json({ success: true, from, to, rekap });
      }
      const rows = await supabaseRest(
        `stock_movements?select=id,type,qty,note,created_at,item_id,stock_items(name,unit,supplier)` +
        `&created_at=gte.${gte}&created_at=lt.${lt}&order=created_at.desc`
      );
      const movements = (rows || []).map(m => ({
        id: m.id, type: m.type, qty: Number(m.qty) || 0, note: m.note || '', createdAt: m.created_at,
        itemId: m.item_id, name: m.stock_items?.name || '(dihapus)', unit: m.stock_items?.unit || '',
        supplier: m.stock_items?.supplier || ''
      }));
      return json({ success: true, from, to, movements });
    }

    if (request.method !== 'POST') return json({ success: false, message: 'Method tidak diizinkan.' }, 405);

    let input;
    try { input = await request.json(); }
    catch { return json({ success: false, message: 'Data JSON tidak valid.' }, 400); }
    if (!checkPublicKey(request, input)) return json({ success: false, message: 'API key tidak valid.' }, 401);

    const qty = Number(input.qty);
    if (!input.itemId) return json({ success: false, message: 'Pilih barang terlebih dahulu.' }, 400);
    if (!['in', 'out'].includes(input.type)) return json({ success: false, message: 'Jenis transaksi tidak valid.' }, 400);
    if (!(qty > 0)) return json({ success: false, message: 'Jumlah harus lebih dari 0.' }, 400);

    const level = await supabaseRest(`stock_levels?id=eq.${encodeURIComponent(input.itemId)}&select=name,unit,stock`);
    if (!level?.length) return json({ success: false, message: 'Barang tidak ditemukan.' }, 404);
    const current = Number(level[0].stock) || 0;
    if (input.type === 'out' && qty > current) {
      return json({ success: false, message: `Stok ${level[0].name} tidak cukup. Sisa: ${current} ${level[0].unit}` }, 409);
    }

    await supabaseRest('stock_movements', {
      method: 'POST', headers: { Prefer: 'return=minimal' },
      body: JSON.stringify([{ item_id: input.itemId, type: input.type, qty, note: cleanText(input.note) }])
    });
    return json({ success: true, stock: input.type === 'in' ? current + qty : current - qty });
  } catch (err) {
    console.error(err);
    return json({ success: false, message: err.message || 'Gagal mencatat stok.' }, 500);
  }
}

export default { fetch: stockMovements };
