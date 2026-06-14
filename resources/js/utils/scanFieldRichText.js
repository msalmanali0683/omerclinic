/**
 * Lightweight scan field markup.
 * Wrap words with double asterisks: **important** → bold on screen and print.
 */

export const SCAN_FIELD_BOLD_HINT = 'Select text and click Bold, or type **word** for bold on print.';

export function escapeHtml(text) {
  return String(text ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

export function normalizeScanFieldPlainText(raw) {
  if (raw == null) {
    return '';
  }

  return String(raw)
    .split(/\r?\n/)
    .map((line) => line.replace(/[^\S\r\n]+/g, ' ').trim())
    .join('\n')
    .trim();
}

export function stripScanFieldBoldMarkers(text) {
  return String(text ?? '').replace(/\*\*/g, '');
}

export function scanFieldHasBoldMarkup(text) {
  return /\*\*.+?\*\*/.test(String(text ?? ''));
}

export function wrapScanFieldBoldSelection(value, selectionStart, selectionEnd) {
  const text = String(value ?? '');
  const start = Math.max(0, Math.min(selectionStart ?? text.length, text.length));
  const end = Math.max(start, Math.min(selectionEnd ?? start, text.length));
  const selected = text.slice(start, end);

  if (!selected) {
    const placeholder = 'text';
    const wrapped = `**${placeholder}**`;

    return {
      value: `${text.slice(0, start)}${wrapped}${text.slice(end)}`,
      selectionStart: start + 2,
      selectionEnd: start + 2 + placeholder.length,
    };
  }

  const before = text.slice(Math.max(0, start - 2), start);
  const after = text.slice(end, end + 2);

  if (before === '**' && after === '**') {
    return {
      value: `${text.slice(0, start - 2)}${selected}${text.slice(end + 2)}`,
      selectionStart: start - 2,
      selectionEnd: end - 2,
    };
  }

  const wrapped = `**${selected}**`;

  return {
    value: `${text.slice(0, start)}${wrapped}${text.slice(end)}`,
    selectionStart: start,
    selectionEnd: start + wrapped.length,
  };
}

export function renderScanFieldRichHtml(raw) {
  const normalized = normalizeScanFieldPlainText(raw);

  if (!normalized) {
    return '';
  }

  return normalized
    .split('\n')
    .map((line) => {
      let html = '';
      let cursor = 0;
      const pattern = /\*\*(.+?)\*\*/g;
      let match = pattern.exec(line);

      while (match) {
        html += escapeHtml(line.slice(cursor, match.index));
        html += `<strong>${escapeHtml(match[1])}</strong>`;
        cursor = match.index + match[0].length;
        match = pattern.exec(line);
      }

      html += escapeHtml(line.slice(cursor));

      return html;
    })
    .join('<br>');
}

export function renderScanFieldRichHtmlFromValue(value) {
  return renderScanFieldRichHtml(value?.field_value);
}
