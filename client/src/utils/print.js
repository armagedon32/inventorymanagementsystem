const esc = (v) => (v == null ? "" : String(v).replace(/</g, "&lt;").replace(/>/g, "&gt;"));

const STYLES = `
  * { box-sizing: border-box; }
  body { font-family: "Times New Roman", Times, serif; margin: 24px; color: #111; }
  .letterhead { text-align: center; margin-bottom: 18px; }
  .letterhead h2 { margin: 0; font-size: 20px; letter-spacing: 1px; }
  .letterhead .addr { font-size: 11px; margin: 2px 0; }
  .doc-title { text-align: center; font-size: 15px; font-weight: bold; margin: 4px 0 14px; }
  table { width: 100%; border-collapse: collapse; font-size: 11px; }
  th, td { border: 1px solid #555; padding: 5px 7px; text-align: left; }
  th { background: #eef2f7; }
  .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 22px; font-size: 11px; margin-bottom: 14px; }
  .meta-grid .lbl { font-weight: bold; }
  .sig { display: flex; justify-content: space-between; margin-top: 60px; }
  .sig div { text-align: center; font-size: 11px; }
  .sig .name { font-weight: bold; border-top: 1px solid #555; padding-top: 4px; min-width: 180px; margin-top: 34px; }
  .no-print { font-family: Arial, sans-serif; margin-bottom: 14px; }
  @media print { .no-print { display: none; } }
`;

export function printDoc({ title, docNo, meta, items, columns, footer = "", signLeft = "", signRight = "", signLeftTitle = "Prepared by:", signRightTitle = "Noted by:" }) {
  const metaRows = (Array.isArray(meta) ? meta : [])
    .map(([l, v]) => `<div><span class="lbl">${esc(l)}:</span> ${esc(v)}</div>`)
    .join("");
  const head = columns.map((c) => `<th>${esc(c.label)}</th>`).join("");
  const body = items
    .map((r) => `<tr>${columns.map((c) => `<td>${esc(r[c.key])}</td>`).join("")}</tr>`)
    .join("");
  const html = `<!doctype html><html><head><meta charset="utf-8"><title>${esc(title)}</title>
    <style>${STYLES}</style>
  </head><body>
    <button class="no-print" onclick="window.print()">Print / Save as PDF</button>
    <div class="letterhead">
      <h2>KOLEHIYO NG SUBIC</h2>
      <div class="addr">WFI Compound, Wawandue, Subic, Zambales</div>
      <div class="addr">Tel.no.: (047)232 – 4896 / 232-4897</div>
    </div>
    <div class="doc-title">${esc(title)}</div>
    ${docNo ? `<div class="doc-title" style="margin-top:-8px;">No. ${esc(docNo)}</div>` : ""}
    <div class="meta-grid">${metaRows}</div>
    <table><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table>
    ${footer ? `<div style="margin-top:10px;font-size:11px;">${footer}</div>` : ""}
    <div class="sig">
      <div>
        <div>${esc(signLeftTitle)}</div>
        <div class="name">${esc(signLeft)}</div>
      </div>
      <div>
        <div>${esc(signRightTitle)}</div>
        <div class="name">${esc(signRight)}</div>
      </div>
    </div>
  </body></html>`;
  const w = window.open("", "_blank", "width=900,height=600");
  if (!w) return;
  w.document.write(html);
  w.document.close();
}