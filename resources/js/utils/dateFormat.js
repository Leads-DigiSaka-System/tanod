/**
 * Global date formatting utilities.
 *
 * formatDate(val)      → "2035-11-10 11:59 PM"   (date + time with AM/PM)
 * formatDateOnly(val)  → "2035-11-10"             (date only)
 * formatTimeOnly(val)  → "11:59:00 PM"            (time only)
 */

function pad(n) {
  return String(n).padStart(2, '0');
}

/**
 * Format a date string / Date object to "YYYY-MM-DD hh:mm AM/PM"
 */
export function formatDate(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (isNaN(d.getTime())) return String(value);

  const year  = d.getFullYear();
  const month = pad(d.getMonth() + 1);
  const day   = pad(d.getDate());

  let hours   = d.getHours();
  const mins  = pad(d.getMinutes());
  const ampm  = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12;

  return `${year}-${month}-${day} ${pad(hours)}:${mins} ${ampm}`;
}

/**
 * Format to "YYYY-MM-DD" only (no time).
 */
export function formatDateOnly(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (isNaN(d.getTime())) return String(value);

  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
}

/**
 * Format to "hh:mm:ss AM/PM" only (no date).
 */
export function formatTimeOnly(value) {
  if (!value) return '—';
  const d = new Date(value);
  if (isNaN(d.getTime())) return String(value);

  let hours  = d.getHours();
  const mins = pad(d.getMinutes());
  const secs = pad(d.getSeconds());
  const ampm = hours >= 12 ? 'PM' : 'AM';
  hours = hours % 12 || 12;

  return `${pad(hours)}:${mins}:${secs} ${ampm}`;
}
