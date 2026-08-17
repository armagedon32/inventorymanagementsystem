function esc(value) {
  return value == null ? "" : String(value);
}

export function exportCSV({ filename, headers, rows }) {
  const head = headers.map((h) => `"${esc(h.label).replace(/"/g, '""')}"`).join(",");
  const body = rows
    .map((r) =>
      headers.map((h) => `"${esc(r[h.key]).replace(/"/g, '""')}"`).join(",")
    )
    .join("\r\n");
  const csv = `\ufeff${head}\r\n${body}\r\n`;
  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

export function exportPDF({ title, filename, headers, rows }) {
  const now = new Date().toLocaleString();
  const head = headers.map((h) => `<th>${esc(h.label)}</th>`).join("");
  const body = rows
    .map(
      (r) =>
        `<tr>${headers.map((h) => `<td>${esc(r[h.key])}</td>`).join("")}</tr>`
    )
    .join("");
  const w = window.open("", "_blank", "width=900,height=600");
  if (!w) return;
  w.document.write(`<!doctype html><html lang="en"><head><meta charset="utf-8">
    <title>${esc(title)}</title>
    <style>
      * { box-sizing: border-box; }
      body { font-family: Arial, Helvetica, sans-serif; margin: 24px; color: #111; }
      h2 { margin: 0 0 4px; }
      .meta { color: #555; font-size: 12px; margin-bottom: 16px; }
      table { width: 100%; border-collapse: collapse; font-size: 11px; }
      th, td { border: 1px solid #999; padding: 5px 7px; text-align: left; }
      th { background: #eef2f7; }
      tr:nth-child(even) td { background: #fafbfd; }
      @media print { .no-print { display: none; } }
    </style>
  </head><body>
    <h2>${esc(title)}</h2>
    <div class="meta">Generated: ${esc(now)} · ${rows.length} record(s)</div>
    <button class="no-print" onclick="window.print()" style="margin-bottom:12px;padding:6px 14px;">Print / Save as PDF</button>
    <table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>
  </body></html>`);
  w.document.close();
}