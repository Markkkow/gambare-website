const SUPABASE_URL = process.env.SUPABASE_URL;
const SUPABASE_SECRET_KEY = process.env.SUPABASE_SECRET_KEY || process.env.SUPABASE_SERVICE_ROLE_KEY;
const PUBLIC_API_KEY = process.env.PUBLIC_API_KEY || '';

export function json(data, status = 200) {
  return new Response(JSON.stringify(data), {
    status,
    headers: {
      'Content-Type': 'application/json; charset=utf-8',
      'Cache-Control': 'no-store'
    }
  });
}

export function assertConfigured() {
  if (!SUPABASE_URL || !SUPABASE_SECRET_KEY) {
    throw new Error('SUPABASE_URL / SUPABASE_SECRET_KEY belum diatur di Vercel Environment Variables.');
  }
}

export function checkPublicKey(request, body = null) {
  if (!PUBLIC_API_KEY) return true;
  const url = new URL(request.url);
  const key = url.searchParams.get('key') || (body && body.key) || request.headers.get('x-api-key');
  return key === PUBLIC_API_KEY;
}

export async function supabaseRest(path, options = {}) {
  assertConfigured();
  const headers = new Headers(options.headers || {});
  headers.set('apikey', SUPABASE_SECRET_KEY);
  headers.set('Authorization', `Bearer ${SUPABASE_SECRET_KEY}`);
  if (!headers.has('Content-Type')) headers.set('Content-Type', 'application/json');

  const res = await fetch(`${SUPABASE_URL}/rest/v1/${path}`, {
    ...options,
    headers
  });

  const text = await res.text();
  let data = null;
  if (text) {
    try { data = JSON.parse(text); }
    catch { data = { raw: text }; }
  }

  if (!res.ok) {
    const message = data?.message || data?.hint || data?.details || `Supabase HTTP ${res.status}`;
    throw new Error(message);
  }
  return data;
}

export function validDate(date) {
  return /^\d{4}-\d{2}-\d{2}$/.test(date || '');
}

export function cleanPhone(value) {
  return String(value || '').replace(/[^0-9+]/g, '');
}

export function cleanText(value) {
  return String(value || '').replace(/[<>]/g, '').trim();
}
