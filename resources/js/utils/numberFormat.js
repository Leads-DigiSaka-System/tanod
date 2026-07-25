const groupedIntegerFormatter = new Intl.NumberFormat('en-US', {
  maximumFractionDigits: 0,
});

const millionFormatter = new Intl.NumberFormat('en-US', {
  maximumFractionDigits: 1,
});

export function formatCount(value) {
  const count = Number(value);

  if (!Number.isFinite(count)) return '0';

  if (Math.abs(count) >= 1_000_000) {
    return `${millionFormatter.format(count / 1_000_000)}M`;
  }

  return groupedIntegerFormatter.format(count);
}
